<?php

namespace Tests\Unit\Erp;

use App\Models\Cliente;
use App\Models\DocumentoComercial;
use App\Models\Empresa;
use App\Models\EstoqueMovimentacao;
use App\Models\LancamentoFinanceiro;
use App\Models\Produto;
use App\Models\Servico;
use App\Models\User;
use App\Services\Erp\DocumentoOrchestrator;
use App\Services\Erp\NfeXmlImporter;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DocumentoOrchestratorTest extends TestCase
{

    public function test_montar_payload_nfce_a_partir_dos_itens(): void
    {
        $empresa = $this->makeEmpresa();
        $produto = Produto::create([
            'empresa_id' => $empresa->id,
            'descricao' => 'Refrigerante',
            'ncm' => '22021000',
            'cfop' => '5102',
            'csosn' => '102',
            'unidade' => 'UN',
            'preco_venda' => 5.5,
            'estoque_atual' => 10,
            'controla_estoque' => true,
            'ativo' => true,
        ]);

        $orch = app(DocumentoOrchestrator::class);
        $doc = $orch->criarRascunho($empresa, [
            'tipo' => DocumentoComercial::TIPO_VENDA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFCE,
            'forma_pagamento' => '01',
            'pago_avista' => true,
            'itens' => [[
                'produto_id' => $produto->id,
                'descricao' => 'Refrigerante',
                'ncm' => '22021000',
                'cfop' => '5102',
                'csosn' => '102',
                'unidade' => 'UN',
                'quantidade' => 2,
                'valor_unitario' => 5.5,
            ]],
        ]);

        $payload = $orch->montarPayloadNfce($doc);

        $this->assertCount(1, $payload['itens']);
        $this->assertSame('Refrigerante', $payload['itens'][0]['descricao']);
        $this->assertEquals(2.0, $payload['itens'][0]['quantidade']);
        $this->assertSame('01', $payload['pagamentos'][0]['t_pag']);
        $this->assertEquals(11.0, $payload['pagamentos'][0]['v_pag']);
    }

    public function test_import_xml_confirma_estoque_e_lancamento(): void
    {
        $empresa = $this->makeEmpresa();

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<nfeProc xmlns="http://www.portalfiscal.inf.br/nfe">
  <NFe>
    <infNFe Id="NFe35200114200166000187550010000000011000000015" versao="4.00">
      <ide><mod>55</mod><serie>1</serie><nNF>99</nNF></ide>
      <emit>
        <CNPJ>14200166000187</CNPJ>
        <xNome>FORNECEDOR XML</xNome>
        <enderEmit><UF>SP</UF><CEP>01001000</CEP></enderEmit>
      </emit>
      <det nItem="1">
        <prod>
          <cProd>SKU1</cProd>
          <cEAN>7891000100103</cEAN>
          <xProd>ITEM XML</xProd>
          <NCM>22021000</NCM>
          <CFOP>1102</CFOP>
          <uCom>UN</uCom>
          <qCom>5.0000</qCom>
          <vUnCom>2.0000</vUnCom>
          <vProd>10.00</vProd>
        </prod>
      </det>
      <total><ICMSTot><vNF>10.00</vNF></ICMSTot></total>
    </infNFe>
  </NFe>
</nfeProc>
XML;

        $doc = app(NfeXmlImporter::class)->importar($empresa, $xml, true);

        $this->assertSame(DocumentoComercial::STATUS_AUTORIZADO, $doc->status);
        $this->assertSame(DocumentoComercial::CANAL_NFE_ENTRADA, $doc->canal_fiscal);
        $this->assertSame('35200114200166000187550010000000011000000015', $doc->chave_nfe);

        $produto = Produto::where('empresa_id', $empresa->id)->where('ean', '7891000100103')->first();
        $this->assertNotNull($produto);
        $this->assertEquals(5.0, (float) $produto->estoque_atual);

        $this->assertTrue(EstoqueMovimentacao::where('documento_comercial_id', $doc->id)->exists());
        $lanc = LancamentoFinanceiro::where('documento_comercial_id', $doc->id)->first();
        $this->assertNotNull($lanc);
        $this->assertSame(LancamentoFinanceiro::TIPO_PAGAR, $lanc->tipo);
        $this->assertEquals(10.0, (float) $lanc->valor);
    }

    public function test_venda_nfse_cria_nota_com_fk_documento(): void
    {
        Queue::fake();

        $empresa = $this->makeEmpresa();
        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'razao_social' => 'CLIENTE',
            'cnpj' => '12345678909',
        ]);
        $servico = Servico::create([
            'empresa_id' => $empresa->id,
            'nome' => 'Consultoria',
            'descricao' => 'Serviço de consultoria',
            'codigo_tributacao_nacional' => '01.01.01',
            'valor_unitario' => 100,
        ]);

        $orch = app(DocumentoOrchestrator::class);
        $doc = $orch->criarRascunho($empresa, [
            'tipo' => DocumentoComercial::TIPO_VENDA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFSE,
            'cliente_id' => $cliente->id,
            'pago_avista' => true,
            'itens' => [[
                'servico_id' => $servico->id,
                'descricao' => 'Consultoria',
                'quantidade' => 1,
                'valor_unitario' => 100,
            ]],
        ]);

        $doc = $orch->confirmar($doc);

        $this->assertSame(DocumentoComercial::STATUS_PROCESSANDO_FISCAL, $doc->status);
        $this->assertNotNull($doc->notaFiscal);
        $this->assertSame($doc->id, $doc->notaFiscal->documento_comercial_id);
        $this->assertSame('processando', $doc->notaFiscal->status);
    }

    private function makeEmpresa(): Empresa
    {
        $user = User::factory()->create();

        return Empresa::create([
            'user_id' => $user->id,
            'cnpj' => '12345678000195',
            'razao_social' => 'EMP TESTE',
            'cep' => '69000000',
            'logradouro' => 'RUA',
            'numero' => '1',
            'bairro' => 'CENTRO',
            'uf' => 'AM',
            'cod_ibge_mun' => '1302603',
            'regime_tributario' => 3,
            'nfce_serie' => 1,
            'nfce_ultimo_numero' => 0,
            'nfce_ambiente' => 2,
            'nfce_csc_id' => '1',
            'nfce_csc_token' => 'TOKEN',
            'crt' => 1,
            'inscricao_estadual' => '123',
        ]);
    }
}
