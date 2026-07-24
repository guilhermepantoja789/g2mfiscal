<?php

namespace Tests\Unit\Erp;

use App\Models\Cliente;
use App\Models\Cobranca;
use App\Models\Empresa;
use App\Models\LancamentoFinanceiro;
use App\Models\User;
use App\Services\Erp\LancamentoFinanceiroService;
use Tests\TestCase;

class LancamentoFinanceiroServiceTest extends TestCase
{
    public function test_criar_baixar_estornar_e_cancelar_manual(): void
    {
        $empresa = $this->makeEmpresa();
        $service = app(LancamentoFinanceiroService::class);

        $lanc = $service->criarManual($empresa, [
            'tipo' => LancamentoFinanceiro::TIPO_RECEBER,
            'valor' => 150.5,
            'vencimento' => now()->addDays(5)->toDateString(),
            'descricao' => 'Manual teste',
        ]);

        $this->assertSame(LancamentoFinanceiro::STATUS_ABERTO, $lanc->status);
        $this->assertEquals(150.5, (float) $lanc->valor);

        $lanc = $service->baixar($lanc);
        $this->assertSame(LancamentoFinanceiro::STATUS_PAGO, $lanc->status);
        $this->assertNotNull($lanc->pago_em);

        $lanc = $service->estornar($lanc);
        $this->assertSame(LancamentoFinanceiro::STATUS_ABERTO, $lanc->status);
        $this->assertNull($lanc->pago_em);

        $lanc = $service->atualizar($lanc, [
            'valor' => 200,
            'descricao' => 'Atualizado',
        ]);
        $this->assertEquals(200.0, (float) $lanc->valor);
        $this->assertSame('Atualizado', $lanc->descricao);

        $lanc = $service->cancelar($lanc);
        $this->assertSame(LancamentoFinanceiro::STATUS_CANCELADO, $lanc->status);
    }

    public function test_ponte_asaas_cria_cobranca_quando_feature_ativa(): void
    {
        config(['services.financeiro.enabled' => true]);

        $empresa = $this->makeEmpresa();
        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'razao_social' => 'Cliente Ponte',
            'cnpj' => '12345678000195',
            'email' => 'c@example.com',
        ]);

        $service = app(LancamentoFinanceiroService::class);
        $lanc = $service->criarManual($empresa, [
            'tipo' => LancamentoFinanceiro::TIPO_RECEBER,
            'valor' => 99,
            'vencimento' => now()->addDay()->toDateString(),
            'cliente_id' => $cliente->id,
            'descricao' => 'P/R com Asaas',
        ]);

        $cobranca = $service->gerarCobrancaAsaas($lanc);
        $this->assertInstanceOf(Cobranca::class, $cobranca);
        $this->assertSame($lanc->id, $cobranca->lancamento_financeiro_id);
        $this->assertSame('RASCUNHO', $cobranca->status);

        $lanc->refresh();
        $this->assertSame($cobranca->id, $lanc->cobranca_id);

        // idempotente
        $again = $service->gerarCobrancaAsaas($lanc);
        $this->assertSame($cobranca->id, $again->id);
    }

    public function test_ponte_asaas_bloqueada_sem_feature(): void
    {
        config(['services.financeiro.enabled' => false]);

        $empresa = $this->makeEmpresa();
        $service = app(LancamentoFinanceiroService::class);
        $lanc = $service->criarManual($empresa, [
            'tipo' => LancamentoFinanceiro::TIPO_RECEBER,
            'valor' => 10,
            'vencimento' => now()->toDateString(),
        ]);

        $this->expectException(\RuntimeException::class);
        $service->gerarCobrancaAsaas($lanc);
    }

    private function makeEmpresa(): Empresa
    {
        $user = User::factory()->create();

        return Empresa::create([
            'user_id' => $user->id,
            'cnpj' => '11222333000181',
            'razao_social' => 'FIN TEST',
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
