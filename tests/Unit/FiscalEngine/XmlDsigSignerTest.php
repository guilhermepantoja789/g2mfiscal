<?php

namespace Tests\Unit\FiscalEngine;

use App\Core\FiscalEngine\Dto\NfceEmitData;
use App\Core\FiscalEngine\Dto\NfceItem;
use App\Core\FiscalEngine\Dto\NfcePayment;
use App\Core\FiscalEngine\Security\XmlDsigSigner;
use App\Core\FiscalEngine\Xml\NfceXmlBuilder;
use PHPUnit\Framework\TestCase;

class XmlDsigSignerTest extends TestCase
{
    public function test_signs_infNFe_with_openssl_rsa_sha1(): void
    {
        [$privatePem, $x509] = $this->generateSelfSigned();

        $built = (new NfceXmlBuilder)->build(new NfceEmitData(
            cnpj: '12345678000195',
            razaoSocial: 'EMPRESA TESTE LTDA',
            nomeFantasia: 'EMPRESA',
            ie: '123456789',
            crt: 1,
            logradouro: 'RUA A',
            numero: '1',
            bairro: 'CENTRO',
            municipio: 'MANAUS',
            uf: 'AM',
            cep: '69000000',
            cMun: '1302603',
            fone: '',
            serie: 1,
            numeroNfce: 1,
            tpAmb: 2,
            itens: [new NfceItem('ITEM', '22021000', '5102', 'UN', 1, 1.00)],
            pagamentos: [new NfcePayment('01', 1.00)],
            cNF: '10000001',
            dhEmi: new \DateTimeImmutable('2025-07-19 12:00:00', new \DateTimeZone('America/Manaus')),
        ));

        $signer = new XmlDsigSigner;
        $digestBefore = $signer->digestValueOfInfNFe($built['dom']);
        $signed = $signer->sign($built['dom'], $privatePem, $x509);
        $xml = $signed->saveXML();

        $this->assertNotFalse($xml);
        $this->assertStringContainsString('<Signature', $xml);
        $this->assertStringContainsString('<SignedInfo>', $xml);
        $this->assertStringContainsString('<DigestValue>'.$digestBefore.'</DigestValue>', $xml);
        $this->assertStringContainsString('<SignatureValue>', $xml);
        $this->assertStringContainsString('<X509Certificate>'.$x509.'</X509Certificate>', $xml);
        $this->assertStringContainsString('rsa-sha1', $xml);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function generateSelfSigned(): array
    {
        $pkey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $this->assertNotFalse($pkey);

        $csr = openssl_csr_new(['commonName' => 'TESTE NFCE'], $pkey, ['digest_alg' => 'sha256']);
        $this->assertNotFalse($csr);

        $cert = openssl_csr_sign($csr, null, $pkey, 365, ['digest_alg' => 'sha256']);
        $this->assertNotFalse($cert);

        openssl_pkey_export($pkey, $privatePem);
        openssl_x509_export($cert, $certPem);

        $x509 = str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n", ' '], '', $certPem);

        return [$privatePem, $x509];
    }
}
