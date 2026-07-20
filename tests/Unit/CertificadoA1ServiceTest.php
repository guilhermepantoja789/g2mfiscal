<?php

namespace Tests\Unit;

use App\Exceptions\CertificadoA1Exception;
use App\Services\CertificadoA1Service;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class CertificadoA1ServiceTest extends TestCase
{
    private CertificadoA1Service $service;

    private string $password = 'senha-teste-123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CertificadoA1Service;
    }

    public function test_rejects_empty_file(): void
    {
        $this->expectException(CertificadoA1Exception::class);
        $this->expectExceptionMessage('vazio');

        $this->service->read('', $this->password);
    }

    public function test_rejects_pem_text(): void
    {
        $this->expectException(CertificadoA1Exception::class);

        $this->service->read("-----BEGIN CERTIFICATE-----\nMIIB\n-----END CERTIFICATE-----", $this->password);
    }

    public function test_reads_modern_pfx(): void
    {
        $pfx = $this->createPfx(legacy: false);

        $result = $this->service->read($pfx, $this->password);

        $this->assertFalse($result->convertedFromLegacy);
        $this->assertNotEmpty($result->pkcs12['cert']);
        $this->assertNotEmpty($result->pkcs12['pkey']);
        $this->assertNotNull($result->certificate);
    }

    public function test_converts_legacy_pfx(): void
    {
        $pfx = $this->createPfx(legacy: true);

        // Confirma que a leitura PHP direta falha (OpenSSL 3 sem legacy)
        $certs = [];
        $directOk = @openssl_pkcs12_read($pfx, $certs, $this->password);
        if ($directOk) {
            $this->markTestSkipped('OpenSSL PHP lê PFX legado sem fallback; conversão não é necessária neste ambiente.');
        }

        $result = $this->service->read($pfx, $this->password);

        $this->assertTrue($result->convertedFromLegacy);
        $this->assertNotEmpty($result->pfxContent);

        // Conteúdo modernizado deve abrir direto
        $modernCerts = [];
        $this->assertTrue(openssl_pkcs12_read($result->pfxContent, $modernCerts, $this->password));
    }

    public function test_wrong_password_on_modern_pfx(): void
    {
        $pfx = $this->createPfx(legacy: false);

        try {
            $this->service->read($pfx, 'senha-errada');
            $this->fail('Deveria ter lançado CertificadoA1Exception');
        } catch (CertificadoA1Exception $e) {
            $this->assertSame(CertificadoA1Exception::WRONG_PASSWORD, $e->codeKey);
            $this->assertSame('senha', $e->formField());
        }
    }

    private function createPfx(bool $legacy): string
    {
        $openssl = $this->service->opensslBinary();
        $this->assertNotNull($openssl, 'OpenSSL CLI é necessário para gerar fixtures de teste');

        $dir = sys_get_temp_dir();
        $key = tempnam($dir, 'tkey_').'.pem';
        $crt = tempnam($dir, 'tcrt_').'.pem';
        $pfx = tempnam($dir, 'tpfx_').'.pfx';

        try {
            $genKey = Process::run([
                $openssl, 'req', '-x509', '-newkey', 'rsa:2048',
                '-keyout', $key, '-out', $crt,
                '-days', '1', '-nodes',
                '-subj', '/CN=Teste G2M Fiscal/',
            ]);
            $this->assertTrue($genKey->successful(), $genKey->errorOutput());

            $exportArgs = [
                $openssl, 'pkcs12', '-export',
                '-inkey', $key, '-in', $crt,
                '-out', $pfx,
                '-passout', 'env:PFX_PASS',
            ];

            if ($legacy) {
                $exportArgs[] = '-legacy';
            } else {
                array_push($exportArgs, '-certpbe', 'AES-256-CBC', '-keypbe', 'AES-256-CBC', '-macalg', 'SHA256');
            }

            $export = Process::env(['PFX_PASS' => $this->password])->run($exportArgs);
            $this->assertTrue($export->successful(), $export->errorOutput());

            $content = file_get_contents($pfx);
            $this->assertNotFalse($content);
            $this->assertNotSame('', $content);

            return $content;
        } finally {
            foreach ([$key, $crt, $pfx] as $path) {
                if (is_string($path) && file_exists($path)) {
                    @unlink($path);
                }
            }
        }
    }
}
