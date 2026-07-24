<?php

namespace Tests\Unit\FiscalEngine;

use App\Core\FiscalEngine\Security\QrCodeGenerator;
use PHPUnit\Framework\TestCase;

class QrCodeGeneratorTest extends TestCase
{
    public function test_cHash_is_uppercase_sha1_hex(): void
    {
        $gen = new QrCodeGenerator('2');
        $params = '131806123456780001956501100000000111234567812345|2|2|1';
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
        $this->assertStringContainsString('|2|2|1|', $result['url']);
        $this->assertStringNotContainsString('|000001|', $result['url']);
        $this->assertStringContainsString($result['cHash'], $result['url']);
        $this->assertSame(40, strlen($result['cHash']));
    }

    public function test_build_strips_leading_zeros_from_csc_id(): void
    {
        $gen = new QrCodeGenerator('2');
        $result = $gen->build(
            chave: '13180612345678000195650110000000011123456781',
            tpAmb: 2,
            cscId: '000001',
            cscToken: 'ABC123',
            baseUrl: 'https://example.com/nfce',
        );

        $this->assertSame(
            '13180612345678000195650110000000011123456781|2|2|1',
            $result['params'],
        );
    }

    public function test_build_contingencia_uses_day_and_hex_digVal(): void
    {
        $gen = new QrCodeGenerator('2');
        $digVal = 'abc+Digest/Value==';
        $digHex = $gen->str2Hex($digVal);
        $result = $gen->build(
            chave: '13180612345678000195650110000000011123456781',
            tpAmb: 2,
            cscId: '1',
            cscToken: 'ABC123',
            baseUrl: 'https://example.com/nfce',
            tpEmis: 9,
            dhEmi: '2026-07-20T10:00:00-04:00',
            vNF: '10.50',
            digVal: $digVal,
        );

        $this->assertSame(
            '13180612345678000195650110000000011123456781|2|2|20|10.50|'.$digHex.'|1',
            $result['params'],
        );
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $digHex);
        $this->assertStringNotContainsString('abc+Digest', $result['params']);
        $this->assertStringNotContainsString('2026-07-20T', $result['params']);
        $this->assertSame(40, strlen($result['cHash']));
        $this->assertStringContainsString($result['cHash'], $result['url']);
    }
}
