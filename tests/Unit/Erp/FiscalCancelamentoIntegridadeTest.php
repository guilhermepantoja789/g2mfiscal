<?php

namespace Tests\Unit\Erp;

use App\Models\DocumentoComercial;
use App\Models\Empresa;
use App\Models\EstoqueMovimentacao;
use App\Models\LancamentoFinanceiro;
use App\Models\Produto;
use App\Models\User;
use App\Services\Erp\DocumentoOrchestrator;
use App\Services\Erp\EstoqueService;
use App\Services\Erp\LancamentoFinanceiroService;
use Tests\TestCase;

class FiscalCancelamentoIntegridadeTest extends TestCase
{
    public function test_cancel_fiscal_estorna_estoque_cancela_pr_e_documento(): void
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
            'custo_medio' => 2.0,
            'estoque_atual' => 20,
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
                'quantidade' => 3,
                'valor_unitario' => 5.5,
            ]],
        ]);

        $doc = $orch->onFiscalAutorizado($doc);

        $produto->refresh();
        $this->assertEquals(17.0, (float) $produto->estoque_atual);
        $this->assertSame(DocumentoComercial::STATUS_AUTORIZADO, $doc->status);

        $lanc = LancamentoFinanceiro::where('documento_comercial_id', $doc->id)->first();
        $this->assertNotNull($lanc);
        $this->assertSame(LancamentoFinanceiro::STATUS_PAGO, $lanc->status);
        $this->assertNotNull($lanc->pago_em);

        $saidas = EstoqueMovimentacao::where('documento_comercial_id', $doc->id)
            ->where('tipo', EstoqueMovimentacao::TIPO_SAIDA)
            ->count();
        $this->assertSame(1, $saidas);

        $motivo = 'Cancelamento teste integridade ERP';
        $doc = $orch->onFiscalCancelado($doc, $motivo);

        $this->assertSame(DocumentoComercial::STATUS_CANCELADO, $doc->status);
        $this->assertSame($motivo, $doc->mensagem_erro);

        $produto->refresh();
        $this->assertEquals(20.0, (float) $produto->estoque_atual);

        $estorno = EstoqueMovimentacao::where('documento_comercial_id', $doc->id)
            ->where('origem', EstoqueService::ORIGEM_ESTORNO_CANCELAMENTO)
            ->first();
        $this->assertNotNull($estorno);
        $this->assertSame(EstoqueMovimentacao::TIPO_ENTRADA, $estorno->tipo);
        $this->assertEquals(3.0, (float) $estorno->quantidade);

        $lanc->refresh();
        $this->assertSame(LancamentoFinanceiro::STATUS_CANCELADO, $lanc->status);
        $this->assertNull($lanc->pago_em);
    }

    public function test_on_fiscal_cancelado_e_idempotente(): void
    {
        $empresa = $this->makeEmpresa();
        $produto = Produto::create([
            'empresa_id' => $empresa->id,
            'descricao' => 'Água',
            'ncm' => '22011000',
            'cfop' => '5102',
            'csosn' => '102',
            'unidade' => 'UN',
            'preco_venda' => 2,
            'custo_medio' => 1,
            'estoque_atual' => 10,
            'controla_estoque' => true,
            'ativo' => true,
        ]);

        $orch = app(DocumentoOrchestrator::class);
        $doc = $orch->criarRascunho($empresa, [
            'tipo' => DocumentoComercial::TIPO_VENDA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFCE,
            'forma_pagamento' => '01',
            'pago_avista' => false,
            'vencimento' => now()->addDays(7)->toDateString(),
            'itens' => [[
                'produto_id' => $produto->id,
                'descricao' => 'Água',
                'ncm' => '22011000',
                'cfop' => '5102',
                'csosn' => '102',
                'unidade' => 'UN',
                'quantidade' => 2,
                'valor_unitario' => 2,
            ]],
        ]);

        $orch->onFiscalAutorizado($doc);
        $orch->onFiscalCancelado($doc->fresh());
        $orch->onFiscalCancelado($doc->fresh());

        $produto->refresh();
        $this->assertEquals(10.0, (float) $produto->estoque_atual);

        $estornos = EstoqueMovimentacao::where('documento_comercial_id', $doc->id)
            ->where('origem', EstoqueService::ORIGEM_ESTORNO_CANCELAMENTO)
            ->count();
        $this->assertSame(1, $estornos);

        $this->assertSame(1, LancamentoFinanceiro::where('documento_comercial_id', $doc->id)
            ->where('status', LancamentoFinanceiro::STATUS_CANCELADO)
            ->count());
    }

    public function test_cancelar_do_documento_cancela_aberto_e_pago(): void
    {
        $empresa = $this->makeEmpresa();
        $doc = DocumentoComercial::create([
            'empresa_id' => $empresa->id,
            'tipo' => DocumentoComercial::TIPO_VENDA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFCE,
            'status' => DocumentoComercial::STATUS_AUTORIZADO,
            'valor_total' => 50,
            'forma_pagamento' => '01',
            'pago_avista' => false,
            'vencimento' => now()->addDay()->toDateString(),
        ]);

        $fin = app(LancamentoFinanceiroService::class);
        $fin->gerarDoDocumento($doc);
        $lanc = LancamentoFinanceiro::where('documento_comercial_id', $doc->id)->firstOrFail();
        $this->assertSame(LancamentoFinanceiro::STATUS_ABERTO, $lanc->status);

        $fin->baixar($lanc);
        $lanc->refresh();
        $this->assertSame(LancamentoFinanceiro::STATUS_PAGO, $lanc->status);

        $fin->cancelarDoDocumento($doc);
        $lanc->refresh();
        $this->assertSame(LancamentoFinanceiro::STATUS_CANCELADO, $lanc->status);
        $this->assertNull($lanc->pago_em);
    }

    private function makeEmpresa(): Empresa
    {
        $user = User::factory()->create();

        return Empresa::create([
            'user_id' => $user->id,
            'cnpj' => '12345678000195',
            'razao_social' => 'EMP CANCEL',
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
