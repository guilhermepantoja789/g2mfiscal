<?php

namespace App\Core\FiscalEngine\Certificates;

use App\Models\Certificado;
use App\Services\CertificadoA1Result;
use App\Services\CertificadoA1Service;
use RuntimeException;

/**
 * Extrai PEM (cert + key) do A1 via CertificadoA1Service para uso em mTLS/assinatura.
 */
class A1Manager
{
    /** @var list<string> */
    private array $tempFiles = [];

    public function __construct(
        private readonly CertificadoA1Service $certificadoA1Service = new CertificadoA1Service,
    ) {}

    public function loadFromModel(Certificado $certificado): CertificadoA1Result
    {
        return $this->certificadoA1Service->loadFromModel($certificado);
    }

    public function loadFromPfx(string $pfxContent, string $password): CertificadoA1Result
    {
        return $this->certificadoA1Service->read($pfxContent, $password);
    }

    /**
     * @return array{cert: string, key: string, result: CertificadoA1Result}
     */
    public function writePemFiles(CertificadoA1Result $result): array
    {
        $certPem = $result->pkcs12['cert'] ?? '';
        $keyPem = $result->pkcs12['pkey'] ?? '';

        if ($certPem === '' || $keyPem === '') {
            throw new RuntimeException('Certificado A1 sem chave pública/privada PEM.');
        }

        $certPath = $this->writeTemp('cert', $certPem);
        $keyPath = $this->writeTemp('key', $keyPem);

        return [
            'cert' => $certPath,
            'key' => $keyPath,
            'result' => $result,
        ];
    }

    public function publicKeyPem(CertificadoA1Result $result): string
    {
        return (string) ($result->pkcs12['cert'] ?? '');
    }

    public function privateKeyPem(CertificadoA1Result $result): string
    {
        return (string) ($result->pkcs12['pkey'] ?? '');
    }

    /**
     * Certificado X509 em Base64 (sem cabeçalhos PEM) para KeyInfo XMLDSIG.
     */
    public function x509CertificateBase64(CertificadoA1Result $result): string
    {
        $pem = $this->publicKeyPem($result);
        $pem = str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n", ' '], '', $pem);

        return $pem;
    }

    public function cleanup(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $this->tempFiles = [];
    }

    public function __destruct()
    {
        $this->cleanup();
    }

    private function writeTemp(string $prefix, string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'nfce_a1_'.$prefix.'_');
        if ($path === false) {
            throw new RuntimeException('Não foi possível criar arquivo temporário PEM.');
        }

        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException('Não foi possível gravar arquivo temporário PEM.');
        }

        $this->tempFiles[] = $path;

        return $path;
    }
}
