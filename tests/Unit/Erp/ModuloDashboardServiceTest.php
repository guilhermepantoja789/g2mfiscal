<?php

namespace Tests\Unit\Erp;

use App\Models\DocumentoComercial;
use App\Models\Empresa;
use App\Models\EstoqueMovimentacao;
use App\Models\Fornecedor;
use App\Models\LancamentoFinanceiro;
use App\Models\Nfce;
use App\Models\Produto;
use App\Models\User;
use App\Services\Erp\ModuloDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tests\TestCase;

class ModuloDashboardServiceTest extends TestCase
{
    public function test_resolve_period_defaults_to_current_month(): void
    {
        $service = app(ModuloDashboardService::class);
        [$inicio, $fim] = $service->resolvePeriod(new Request);

        $this->assertTrue($inicio->equalTo(Carbon::now()->startOfMonth()));
        $this->assertTrue($fim->equalTo(Carbon::now()->endOfMonth()));
    }

    public function test_financeiro_agrega_abertos_e_baixas(): void
    {
        $empresa = $this->makeEmpresa();
        $inicio = Carbon::now()->startOfMonth();
        $fim = Carbon::now()->endOfMonth();

        LancamentoFinanceiro::create([
            'empresa_id' => $empresa->id,
            'tipo' => LancamentoFinanceiro::TIPO_RECEBER,
            'status' => LancamentoFinanceiro::STATUS_ABERTO,
            'valor' => 100,
            'vencimento' => Carbon::tomorrow(),
            'descricao' => 'Receber aberto',
        ]);
        LancamentoFinanceiro::create([
            'empresa_id' => $empresa->id,
            'tipo' => LancamentoFinanceiro::TIPO_PAGAR,
            'status' => LancamentoFinanceiro::STATUS_ABERTO,
            'valor' => 40,
            'vencimento' => Carbon::yesterday(),
            'descricao' => 'Pagar vencido',
        ]);
        LancamentoFinanceiro::create([
            'empresa_id' => $empresa->id,
            'tipo' => LancamentoFinanceiro::TIPO_RECEBER,
            'status' => LancamentoFinanceiro::STATUS_PAGO,
            'valor' => 50,
            'vencimento' => Carbon::today(),
            'pago_em' => Carbon::now(),
            'descricao' => 'Recebido',
        ]);

        $stats = app(ModuloDashboardService::class)->financeiro($empresa->id, $inicio, $fim);

        $this->assertEquals(100.0, $stats['a_receber_aberto']);
        $this->assertEquals(40.0, $stats['a_pagar_aberto']);
        $this->assertEquals(40.0, $stats['vencidos_pagar']);
        $this->assertEquals(50.0, $stats['recebido_periodo']);
    }

    public function test_estoque_calcula_valor_e_movimentacoes(): void
    {
        $empresa = $this->makeEmpresa();
        $produto = Produto::create([
            'empresa_id' => $empresa->id,
            'descricao' => 'Item',
            'ncm' => '22021000',
            'cfop' => '5102',
            'csosn' => '102',
            'unidade' => 'UN',
            'preco_venda' => 20,
            'custo_medio' => 5,
            'estoque_atual' => 10,
            'controla_estoque' => true,
            'ativo' => true,
        ]);

        EstoqueMovimentacao::create([
            'empresa_id' => $empresa->id,
            'produto_id' => $produto->id,
            'tipo' => EstoqueMovimentacao::TIPO_SAIDA,
            'quantidade' => 2,
            'custo_unitario' => 5,
            'saldo_apos' => 8,
            'origem' => 'teste',
        ]);

        $stats = app(ModuloDashboardService::class)->estoque(
            $empresa->id,
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        );

        $this->assertEquals(50.0, $stats['valor_estoque']);
        $this->assertEquals(1, $stats['com_saldo']);
        $this->assertEquals(2.0, $stats['saidas']);
    }

    public function test_documentos_faturamento_autorizado(): void
    {
        $empresa = $this->makeEmpresa();
        DocumentoComercial::create([
            'empresa_id' => $empresa->id,
            'tipo' => DocumentoComercial::TIPO_VENDA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFCE,
            'status' => DocumentoComercial::STATUS_AUTORIZADO,
            'valor_total' => 150,
            'forma_pagamento' => '01',
            'pago_avista' => true,
        ]);
        DocumentoComercial::create([
            'empresa_id' => $empresa->id,
            'tipo' => DocumentoComercial::TIPO_VENDA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFCE,
            'status' => DocumentoComercial::STATUS_RASCUNHO,
            'valor_total' => 999,
            'forma_pagamento' => '01',
            'pago_avista' => true,
        ]);

        $stats = app(ModuloDashboardService::class)->documentos(
            $empresa->id,
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        );

        $this->assertEquals(150.0, $stats['faturamento']);
        $this->assertEquals(1, $stats['funil'][DocumentoComercial::STATUS_AUTORIZADO] ?? 0);
    }

    public function test_nfce_conta_autorizadas(): void
    {
        $empresa = $this->makeEmpresa();
        Nfce::create([
            'empresa_id' => $empresa->id,
            'status' => 'autorizada',
            'valor_total' => 80,
            'ambiente' => 2,
            'tp_emis' => 1,
            'serie' => 1,
            'numero' => 1,
        ]);
        Nfce::create([
            'empresa_id' => $empresa->id,
            'status' => 'rejeitada',
            'valor_total' => 10,
            'ambiente' => 2,
            'tp_emis' => 9,
            'serie' => 1,
            'numero' => 2,
        ]);

        $stats = app(ModuloDashboardService::class)->nfce(
            $empresa->id,
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        );

        $this->assertEquals(1, $stats['autorizadas_qtd']);
        $this->assertEquals(80.0, $stats['autorizadas_valor']);
        $this->assertEquals(1, $stats['erros']);
        $this->assertEquals(1, $stats['contingencia']);
    }

    public function test_produtos_e_fornecedores_resumo(): void
    {
        $empresa = $this->makeEmpresa();
        Produto::create([
            'empresa_id' => $empresa->id,
            'descricao' => 'P1',
            'cfop' => '5102',
            'csosn' => '102',
            'unidade' => 'UN',
            'preco_venda' => 30,
            'custo_medio' => 10,
            'estoque_atual' => 2,
            'controla_estoque' => true,
            'ativo' => true,
        ]);
        $fornecedor = Fornecedor::create([
            'empresa_id' => $empresa->id,
            'razao_social' => 'Fornecedor X',
            'cnpj' => '11222333000181',
        ]);
        DocumentoComercial::create([
            'empresa_id' => $empresa->id,
            'tipo' => DocumentoComercial::TIPO_COMPRA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFE_ENTRADA,
            'status' => DocumentoComercial::STATUS_AUTORIZADO,
            'fornecedor_id' => $fornecedor->id,
            'valor_total' => 200,
            'forma_pagamento' => '01',
            'pago_avista' => true,
        ]);

        $produtos = app(ModuloDashboardService::class)->produtos($empresa->id);
        $this->assertEquals(1, $produtos['total']);
        $this->assertEquals(60.0, $produtos['valor_potencial']);

        $fornecedores = app(ModuloDashboardService::class)->fornecedores(
            $empresa->id,
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth(),
            true
        );
        $this->assertEquals(1, $fornecedores['total']);
        $this->assertEquals(200.0, $fornecedores['compras_total']);
    }

    private function makeEmpresa(): Empresa
    {
        $user = User::factory()->create();

        return Empresa::create([
            'user_id' => $user->id,
            'cnpj' => '12345678000195',
            'razao_social' => 'EMP DASH',
            'cep' => '69000000',
            'logradouro' => 'RUA',
            'numero' => '1',
            'bairro' => 'CENTRO',
            'uf' => 'AM',
            'cod_ibge_mun' => '1302603',
            'regime_tributario' => 3,
        ]);
    }
}
