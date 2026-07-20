<?php

namespace App\Services;

use App\Exceptions\CertificadoA1Exception;
use App\Models\Certificado;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use NFePHP\Common\Certificate;
use Throwable;

class CertificadoA1Service
{
    /**
     * Lê e valida um PFX/P12 em memória.
     * Se o arquivo usar algoritmos legados (OpenSSL 3), converte para PFX moderno.
     *
     * @throws CertificadoA1Exception
     */
    public function read(string $pfxContent, string $password): CertificadoA1Result
    {
        $this->assertNonEmpty($pfxContent);
        $this->assertLooksLikePkcs12($pfxContent);

        while (openssl_error_string() !== false) {
            // drena fila de erros anteriores
        }

        $certs = [];
        if (@openssl_pkcs12_read($pfxContent, $certs, $password)) {
            return $this->buildResult($pfxContent, $certs, $password, convertedFromLegacy: false);
        }

        $directErrors = $this->drainOpensslErrors();
        $directLower = mb_strtolower($directErrors);

        // Senha claramente inválida: não tenta conversão legada.
        if ($this->looksLikeWrongPassword($directLower) && ! $this->looksLikeUnsupportedCipher($directLower)) {
            throw new CertificadoA1Exception(
                'A senha informada está incorreta. Verifique a senha definida na emissão/exportação do certificado A1.',
                CertificadoA1Exception::WRONG_PASSWORD,
                $directErrors,
            );
        }

        try {
            $modernPfx = $this->convertLegacyPfx($pfxContent, $password);
        } catch (CertificadoA1Exception $e) {
            throw $e;
        } catch (Throwable $e) {
            throw $this->mapReadFailure($directErrors, $e);
        }

        $modernCerts = [];
        if (! @openssl_pkcs12_read($modernPfx, $modernCerts, $password)) {
            $afterConvertErrors = $this->drainOpensslErrors();
            throw new CertificadoA1Exception(
                'O certificado foi convertido do formato legado, mas ainda não pôde ser lido. Tente reexportar o .pfx no computador onde o certificado foi gerado.',
                CertificadoA1Exception::LEGACY_UNSUPPORTED,
                $afterConvertErrors ?: $directErrors,
            );
        }

        return $this->buildResult($modernPfx, $modernCerts, $password, convertedFromLegacy: true);
    }

    /**
     * Carrega o certificado persistido da empresa; se precisar de conversão legada,
     * reescreve o arquivo no storage em formato moderno.
     *
     * @throws CertificadoA1Exception
     */
    public function loadFromModel(Certificado $certificado, bool $rewriteIfConverted = true): CertificadoA1Result
    {
        if (! $certificado->nome_arquivo || ! Storage::exists($certificado->nome_arquivo)) {
            throw new CertificadoA1Exception(
                'Arquivo do certificado não encontrado no armazenamento.',
                CertificadoA1Exception::INVALID_FORMAT,
            );
        }

        $pfxContent = Storage::get($certificado->nome_arquivo);
        $result = $this->read($pfxContent, (string) $certificado->senha);

        if ($rewriteIfConverted && $result->convertedFromLegacy) {
            Storage::put($certificado->nome_arquivo, $result->pfxContent);
            Log::info('Certificado A1 reescrito em formato moderno (OpenSSL legacy).', [
                'certificado_id' => $certificado->id,
                'empresa_id' => $certificado->empresa_id,
            ]);
        }

        return $result;
    }

    public function opensslVersion(): string
    {
        return defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'desconhecido';
    }

    public function opensslBinary(): ?string
    {
        $candidates = [
            '/opt/homebrew/bin/openssl',
            '/usr/local/bin/openssl',
            '/usr/bin/openssl',
        ];

        foreach ($candidates as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        $which = Process::run(['which', 'openssl']);
        if ($which->successful()) {
            $path = trim($which->output());

            return $path !== '' ? $path : null;
        }

        return null;
    }

    /**
     * @param  array{cert?: string, pkey?: string, extracerts?: array}  $certs
     */
    protected function buildResult(
        string $pfxContent,
        array $certs,
        string $password,
        bool $convertedFromLegacy,
    ): CertificadoA1Result {
        if (empty($certs['cert']) || empty($certs['pkey'])) {
            throw new CertificadoA1Exception(
                'O arquivo PKCS#12 não contém certificado e chave privada.',
                CertificadoA1Exception::INVALID_FORMAT,
            );
        }

        $x509 = openssl_x509_parse($certs['cert']);
        if ($x509 === false || empty($x509['validTo_time_t'])) {
            throw new CertificadoA1Exception(
                'Não foi possível extrair os dados do certificado digital.',
                CertificadoA1Exception::INVALID_FORMAT,
                $this->drainOpensslErrors(),
            );
        }

        $validoAte = (new DateTimeImmutable)->setTimestamp((int) $x509['validTo_time_t']);

        try {
            $certificate = Certificate::readPfx($pfxContent, $password);
        } catch (Throwable $e) {
            throw new CertificadoA1Exception(
                'Falha ao carregar o certificado na biblioteca NFePHP: '.$e->getMessage(),
                CertificadoA1Exception::READ_FAILED,
                previous: $e,
            );
        }

        return new CertificadoA1Result(
            pfxContent: $pfxContent,
            pkcs12: $certs,
            x509: $x509,
            validoAte: $validoAte,
            convertedFromLegacy: $convertedFromLegacy,
            certificate: $certificate,
            opensslVersion: $this->opensslVersion(),
        );
    }

    /**
     * @throws CertificadoA1Exception
     */
    protected function convertLegacyPfx(string $pfxContent, string $password): string
    {
        $openssl = $this->opensslBinary();
        if ($openssl === null) {
            throw new CertificadoA1Exception(
                'Não foi possível ler o certificado. O OpenSSL CLI não está disponível para converter formatos legados (RC2/3DES comuns em A1 brasileiros).',
                CertificadoA1Exception::LEGACY_UNSUPPORTED,
            );
        }

        $tmpDir = sys_get_temp_dir();
        $inPath = tempnam($tmpDir, 'pfx_in_');
        $pemPath = tempnam($tmpDir, 'pfx_pem_');
        $outPath = tempnam($tmpDir, 'pfx_out_');

        if ($inPath === false || $pemPath === false || $outPath === false) {
            throw new CertificadoA1Exception(
                'Falha ao criar arquivos temporários para conversão do certificado.',
                CertificadoA1Exception::READ_FAILED,
            );
        }

        $inPfx = $inPath.'.pfx';
        $outPfx = $outPath.'.pfx';
        $pemFile = $pemPath.'.pem';

        try {
            file_put_contents($inPfx, $pfxContent);

            $import = Process::env([
                'PFX_PASS' => $password,
            ])->run([
                $openssl,
                'pkcs12',
                '-in', $inPfx,
                '-legacy',
                '-nodes',
                '-passin', 'env:PFX_PASS',
                '-out', $pemFile,
            ]);

            if (! $import->successful() || ! is_readable($pemFile) || filesize($pemFile) === 0) {
                $stderr = trim($import->errorOutput()."\n".$import->output());
                throw $this->mapLegacyImportFailure($stderr);
            }

            $export = Process::env([
                'PFX_PASS' => $password,
            ])->run([
                $openssl,
                'pkcs12',
                '-export',
                '-in', $pemFile,
                '-out', $outPfx,
                '-passout', 'env:PFX_PASS',
                '-certpbe', 'AES-256-CBC',
                '-keypbe', 'AES-256-CBC',
                '-macalg', 'SHA256',
            ]);

            if (! $export->successful() || ! is_readable($outPfx)) {
                $stderr = trim($export->errorOutput()."\n".$export->output());
                throw new CertificadoA1Exception(
                    'A senha parece correta, mas falhou a conversão do certificado para formato moderno. Tente reexportar o .pfx.',
                    CertificadoA1Exception::LEGACY_UNSUPPORTED,
                    $stderr,
                );
            }

            $modern = file_get_contents($outPfx);
            if ($modern === false || $modern === '') {
                throw new CertificadoA1Exception(
                    'Conversão do certificado gerou arquivo vazio.',
                    CertificadoA1Exception::LEGACY_UNSUPPORTED,
                );
            }

            return $modern;
        } finally {
            foreach ([$inPath, $pemPath, $outPath, $inPfx, $pemFile, $outPfx] as $path) {
                if (is_string($path) && file_exists($path)) {
                    @unlink($path);
                }
            }
        }
    }

    protected function mapLegacyImportFailure(string $stderr): CertificadoA1Exception
    {
        $lower = mb_strtolower($stderr);

        if ($this->looksLikeWrongPassword($lower)) {
            return new CertificadoA1Exception(
                'A senha informada está incorreta. Verifique a senha definida na emissão/exportação do certificado A1.',
                CertificadoA1Exception::WRONG_PASSWORD,
                $stderr,
            );
        }

        if (str_contains($lower, 'legacy') || str_contains($lower, 'unsupported') || str_contains($lower, 'provider')) {
            return new CertificadoA1Exception(
                'O certificado usa criptografia legada e a conversão falhou neste ambiente. Instale OpenSSL 3 com provider legacy ou reexporte o .pfx com algoritmos modernos (AES).',
                CertificadoA1Exception::LEGACY_UNSUPPORTED,
                $stderr,
            );
        }

        return new CertificadoA1Exception(
            'Não foi possível ler o certificado (formato legado ou arquivo inválido). Confirme a senha ou reexporte o arquivo .pfx/.p12.',
            CertificadoA1Exception::READ_FAILED,
            $stderr,
        );
    }

    protected function mapReadFailure(string $directErrors, Throwable $conversionError): CertificadoA1Exception
    {
        if ($conversionError instanceof CertificadoA1Exception) {
            return $conversionError;
        }

        $combined = mb_strtolower($directErrors.' '.$conversionError->getMessage());

        if ($this->looksLikeWrongPassword($combined)) {
            return new CertificadoA1Exception(
                'A senha informada está incorreta. Verifique a senha definida na emissão/exportação do certificado A1.',
                CertificadoA1Exception::WRONG_PASSWORD,
                $directErrors,
                previous: $conversionError,
            );
        }

        if ($this->looksLikeUnsupportedCipher($combined)) {
            return new CertificadoA1Exception(
                'O certificado parece usar algoritmos legados (RC2/3DES) incompatíveis com OpenSSL 3. A conversão automática falhou — reexporte o .pfx ou verifique se o OpenSSL CLI com `-legacy` está disponível.',
                CertificadoA1Exception::LEGACY_UNSUPPORTED,
                $directErrors,
                previous: $conversionError,
            );
        }

        return new CertificadoA1Exception(
            'Não foi possível ler o certificado. Confirme a senha ou se o arquivo .pfx/.p12 não está corrompido.',
            CertificadoA1Exception::READ_FAILED,
            $directErrors,
            previous: $conversionError,
        );
    }

    protected function looksLikeWrongPassword(string $lowerError): bool
    {
        return str_contains($lowerError, 'mac verify')
            || str_contains($lowerError, 'mac verify failure')
            || str_contains($lowerError, 'invalid password')
            || str_contains($lowerError, 'password supplied')
            || (str_contains($lowerError, 'password') && str_contains($lowerError, 'fail'));
    }

    protected function looksLikeUnsupportedCipher(string $lowerError): bool
    {
        return str_contains($lowerError, 'unsupported')
            || str_contains($lowerError, 'digital envelope')
            || str_contains($lowerError, 'rc2')
            || str_contains($lowerError, 'algorithm');
    }

    protected function assertNonEmpty(string $pfxContent): void
    {
        if ($pfxContent === '') {
            throw new CertificadoA1Exception(
                'O arquivo do certificado está vazio.',
                CertificadoA1Exception::EMPTY,
            );
        }
    }

    protected function assertLooksLikePkcs12(string $pfxContent): void
    {
        $first = ord($pfxContent[0]);
        if ($first !== 0x30) {
            $trimmed = ltrim($pfxContent);
            if (str_starts_with($trimmed, '-----BEGIN')) {
                throw new CertificadoA1Exception(
                    'O arquivo parece ser PEM/texto. Envie o certificado no formato .pfx ou .p12 (PKCS#12).',
                    CertificadoA1Exception::INVALID_FORMAT,
                );
            }

            throw new CertificadoA1Exception(
                'O arquivo não parece ser um PKCS#12 válido (.pfx/.p12). Verifique se o upload não foi corrompido.',
                CertificadoA1Exception::INVALID_FORMAT,
            );
        }
    }

    protected function drainOpensslErrors(): string
    {
        $errors = [];
        while ($err = openssl_error_string()) {
            $errors[] = $err;
        }

        return implode(' | ', $errors);
    }
}
