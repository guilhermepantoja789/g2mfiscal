<?php

namespace Tests\Unit;

use App\Services\NfseIbscbsBuilder;
use PHPUnit\Framework\TestCase;

class NfseIbscbsBuilderTest extends TestCase
{
    public function test_resolves_defaults_and_ind_final_from_cnpj(): void
    {
        $r = NfseIbscbsBuilder::resolve([
            'tomador_doc' => '12345678000195',
        ]);

        $this->assertSame('0', $r['fin_nfse']);
        $this->assertSame('0', $r['ind_final']);
        $this->assertSame('0', $r['ind_dest']);
        $this->assertSame('100301', $r['c_ind_op']);
        $this->assertSame('000', $r['cst']);
        $this->assertSame('000001', $r['c_class_trib']);
    }

    public function test_resolves_ind_final_from_cpf(): void
    {
        $r = NfseIbscbsBuilder::resolve([
            'tomador_doc' => '12345678909',
        ]);

        $this->assertSame('1', $r['ind_final']);
    }

    public function test_xml_contains_required_tags_in_order(): void
    {
        $xml = NfseIbscbsBuilder::toXml([
            'tomador_doc' => '12345678000195',
            'c_ind_op' => '100301',
            'cst_ibscbs' => '000',
            'c_class_trib' => '000001',
        ]);

        $this->assertStringContainsString('<IBSCBS>', $xml);
        $this->assertStringContainsString('<finNFSe>0</finNFSe>', $xml);
        $this->assertStringContainsString('<indFinal>0</indFinal>', $xml);
        $this->assertStringContainsString('<cIndOp>100301</cIndOp>', $xml);
        $this->assertStringContainsString('<indDest>0</indDest>', $xml);
        $this->assertStringContainsString('<CST>000</CST>', $xml);
        $this->assertStringContainsString('<cClassTrib>000001</cClassTrib>', $xml);

        $posFin = strpos($xml, '<finNFSe>');
        $posIndFinal = strpos($xml, '<indFinal>');
        $posCInd = strpos($xml, '<cIndOp>');
        $posIndDest = strpos($xml, '<indDest>');
        $posValores = strpos($xml, '<valores>');

        $this->assertTrue($posFin < $posIndFinal);
        $this->assertTrue($posIndFinal < $posCInd);
        $this->assertTrue($posCInd < $posIndDest);
        $this->assertTrue($posIndDest < $posValores);
    }
}
