<?php

namespace Tests\Unit\Erp;

use App\Services\Erp\NfeXmlImporter;
use InvalidArgumentException;
use Tests\TestCase;

class NfeXmlImporterTest extends TestCase
{
    public function test_parse_extrai_fornecedor_itens_e_totais(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<nfeProc xmlns="http://www.portalfiscal.inf.br/nfe">
  <NFe>
    <infNFe Id="NFe35200114200166000187550010000000011000000015" versao="4.00">
      <ide>
        <mod>55</mod>
        <serie>1</serie>
        <nNF>1</nNF>
      </ide>
      <emit>
        <CNPJ>14200166000187</CNPJ>
        <xNome>FORNECEDOR TESTE LTDA</xNome>
        <IE>123456789</IE>
        <enderEmit>
          <xLgr>RUA A</xLgr>
          <nro>100</nro>
          <xBairro>CENTRO</xBairro>
          <cMun>3550308</cMun>
          <UF>SP</UF>
          <CEP>01001000</CEP>
        </enderEmit>
      </emit>
      <det nItem="1">
        <prod>
          <cProd>ABC123</cProd>
          <cEAN>7891000100103</cEAN>
          <xProd>PRODUTO ALPHA</xProd>
          <NCM>22021000</NCM>
          <CFOP>5102</CFOP>
          <uCom>UN</uCom>
          <qCom>2.0000</qCom>
          <vUnCom>10.5000</vUnCom>
          <vProd>21.00</vProd>
        </prod>
      </det>
      <total>
        <ICMSTot>
          <vNF>21.00</vNF>
        </ICMSTot>
      </total>
    </infNFe>
  </NFe>
</nfeProc>
XML;

        $parsed = app(NfeXmlImporter::class)->parse($xml);

        $this->assertSame('35200114200166000187550010000000011000000015', $parsed['chave']);
        $this->assertSame('1', $parsed['numero']);
        $this->assertSame('1', $parsed['serie']);
        $this->assertEquals(21.0, $parsed['valor_total']);
        $this->assertSame('14200166000187', $parsed['fornecedor']['cnpj']);
        $this->assertSame('FORNECEDOR TESTE LTDA', $parsed['fornecedor']['razao_social']);
        $this->assertCount(1, $parsed['itens']);
        $this->assertSame('PRODUTO ALPHA', $parsed['itens'][0]['descricao']);
        $this->assertSame('7891000100103', $parsed['itens'][0]['ean']);
        $this->assertEquals(2.0, $parsed['itens'][0]['quantidade']);
        $this->assertEquals(10.5, $parsed['itens'][0]['valor_unitario']);
    }

    public function test_rejeita_modelo_diferente_de_55(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<NFe xmlns="http://www.portalfiscal.inf.br/nfe">
  <infNFe Id="NFe1" versao="4.00">
    <ide><mod>65</mod><serie>1</serie><nNF>1</nNF></ide>
    <emit><CNPJ>14200166000187</CNPJ><xNome>X</xNome></emit>
  </infNFe>
</NFe>
XML;

        app(NfeXmlImporter::class)->parse($xml);
    }
}
