<?php

namespace Tests\Unit\Erp;

use App\Models\DocumentoComercial;
use App\Models\Empresa;
use App\Models\FormaPagamento;
use App\Models\LancamentoFinanceiro;
use App\Models\User;
use App\Services\Erp\FormaPagamentoService;
use App\Services\Erp\LancamentoFinanceiroService;
use Carbon\Carbon;
use Tests\TestCase;

class FormaPagamentoServiceTest extends TestCase
{

    public function test_garantir_defaults_cria_quatro_formas(): void
    {
        $empresa = $this->makeEmpresa();
        $service = app(FormaPagamentoService::class);

        $formas = $service->garantirDefaults($empresa);
        $this->assertCount(4, $formas);

        $again = $service->garantirDefaults($empresa);
        $this->assertCount(4, $again);
        $this->assertSame(4, FormaPagamento::where('empresa_id', $empresa->id)->count());
    }

    public function test_calcular_parcelas_com_juros_e_centavos(): void
    {
        $empresa = $this->makeEmpresa();
        $forma = FormaPagamento::create([
            'empresa_id' => $empresa->id,
            'codigo' => '03',
            'nome' => 'Crédito 3x',
            'ativo' => true,
            'tipo_liquidacao' => FormaPagamento::TIPO_PRAZO,
            'dias_recebimento' => 30,
            'parcelas' => 3,
            'juros_percentual' => 10,
            'gera_lancamento' => true,
        ]);

        $parcelas = app(FormaPagamentoService::class)
            ->calcularParcelas($forma, 100.0, Carbon::parse('2026-01-01'));

        $this->assertCount(3, $parcelas);
        $soma = round(array_sum(array_column($parcelas, 'valor')), 2);
        $this->assertEquals(110.0, $soma);
        $this->assertSame('2026-01-31', $parcelas[0]['vencimento']);
        $this->assertSame('2026-03-02', $parcelas[1]['vencimento']);
        $this->assertSame('2026-04-01', $parcelas[2]['vencimento']);
        $this->assertFalse($parcelas[0]['pago_avista']);
    }

    public function test_gerar_lancamentos_multiplos_do_documento(): void
    {
        $empresa = $this->makeEmpresa();
        $forma = FormaPagamento::create([
            'empresa_id' => $empresa->id,
            'codigo' => '03',
            'nome' => 'Crédito 2x',
            'ativo' => true,
            'tipo_liquidacao' => FormaPagamento::TIPO_PRAZO,
            'dias_recebimento' => 30,
            'parcelas' => 2,
            'juros_percentual' => 0,
            'gera_lancamento' => true,
        ]);

        $doc = DocumentoComercial::create([
            'empresa_id' => $empresa->id,
            'tipo' => DocumentoComercial::TIPO_VENDA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFCE,
            'status' => DocumentoComercial::STATUS_AUTORIZADO,
            'valor_total' => 100,
            'forma_pagamento' => '03',
            'forma_pagamento_id' => $forma->id,
            'pago_avista' => false,
        ]);

        $lancamentos = app(LancamentoFinanceiroService::class)->gerarDoDocumento($doc);

        $this->assertCount(2, $lancamentos);
        $this->assertEquals(50.0, (float) $lancamentos[0]->valor);
        $this->assertEquals(50.0, (float) $lancamentos[1]->valor);
        $this->assertSame(1, $lancamentos[0]->parcela);
        $this->assertSame(2, $lancamentos[0]->total_parcelas);
        $this->assertSame(LancamentoFinanceiro::STATUS_ABERTO, $lancamentos[0]->status);
        $this->assertSame(LancamentoFinanceiro::TIPO_RECEBER, $lancamentos[0]->tipo);

        $again = app(LancamentoFinanceiroService::class)->gerarDoDocumento($doc);
        $this->assertCount(2, $again);
        $this->assertSame(2, LancamentoFinanceiro::where('documento_comercial_id', $doc->id)->count());
    }

    public function test_avista_marca_lancamento_pago(): void
    {
        $empresa = $this->makeEmpresa();
        $forma = FormaPagamento::create([
            'empresa_id' => $empresa->id,
            'codigo' => '01',
            'nome' => 'Dinheiro',
            'ativo' => true,
            'tipo_liquidacao' => FormaPagamento::TIPO_AVISTA,
            'dias_recebimento' => 0,
            'parcelas' => 1,
            'juros_percentual' => 0,
            'gera_lancamento' => true,
        ]);

        $doc = DocumentoComercial::create([
            'empresa_id' => $empresa->id,
            'tipo' => DocumentoComercial::TIPO_VENDA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFCE,
            'status' => DocumentoComercial::STATUS_AUTORIZADO,
            'valor_total' => 25.5,
            'forma_pagamento' => '01',
            'forma_pagamento_id' => $forma->id,
            'pago_avista' => true,
        ]);

        $lancamentos = app(LancamentoFinanceiroService::class)->gerarDoDocumento($doc);
        $this->assertCount(1, $lancamentos);
        $this->assertSame(LancamentoFinanceiro::STATUS_PAGO, $lancamentos[0]->status);
        $this->assertNotNull($lancamentos[0]->pago_em);
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
