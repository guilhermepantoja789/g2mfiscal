<?php

namespace Tests\Unit\FiscalEngine;

use App\Core\FiscalEngine\Xml\InutilizacaoXmlBuilder;
use PHPUnit\Framework\TestCase;

class InutilizacaoXmlBuilderTest extends TestCase
{
    public function test_build_inutilizacao(): void
    {
        $built = (new InutilizacaoXmlBuilder)->build(
            cnpj: '12345678000195',
            serie: 1,
            nNFIni: 10,
            nNFFin: 12,
            xJust: 'Numeracao inutilizada por erro operacional',
            tpAmb: 2,
            ano: 26,
        );

        $this->assertStringStartsWith('ID13', $built['id']);
        $xml = $built['xml'];
        $this->assertStringContainsString('<xServ>INUTILIZAR</xServ>', $xml);
        $this->assertStringContainsString('<mod>65</mod>', $xml);
        $this->assertStringContainsString('<nNFIni>10</nNFIni>', $xml);
        $this->assertStringContainsString('<nNFFin>12</nNFFin>', $xml);
        $this->assertStringContainsString('Id="'.$built['id'].'"', $xml);
    }
}
