<?php

namespace Tests\Unit\FiscalEngine;

use App\Core\FiscalEngine\Security\QrCodeGenerator;
use PHPUnit\Framework\TestCase;

class QrCodeGeneratorTest extends TestCase
{
    public function test_cHash_is_uppercase_sha1_hex(): void
    {
        $gen = new QrCodeGenerator('2');
        $params = '131806123456780001956501100000000111234567812345|2|2|000001';
        $token = 'TOKENCSCFAKE';
        $expected = strtoupper(sha1($params.$token));

        $this->assertSame($expected, $gen->cHash($params, $token));
    }

    public function test_build_url_contains_hash(): void
    {
        $gen = new QrCodeGenerator('2');
        $chave = '13180612345678000195650110000000011123456781';
        $result = $gen->build(
            chave: $chave,
            tpAmb: 2,
            cscId: '1',
            cscToken: 'ABC123',
            baseUrl: 'https://sistemas.sefaz.am.gov.br/nfceweb-hom/consultarNFCe.jsp',
        );

        $this->assertStringContainsString('p=', $result['url']);
        $this->assertStringContainsString($result['cHash'], $result['url']);
        $this->assertSame(40, strlen($result['cHash']));
    }
}
