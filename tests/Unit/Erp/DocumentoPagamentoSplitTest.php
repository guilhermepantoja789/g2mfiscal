<?php

namespace Tests\Unit\Erp;

use App\Models\ContaContabil;
use App\Models\DocumentoComercial;
use App\Models\DocumentoPagamento;
use App\Models\Empresa;
use App\Models\FormaPagamento;
use App\Models\LancamentoContabil;
use App\Models\LancamentoContabilItem;
use App\Models\LancamentoFinanceiro;
use App\Models\Produto;
use App\Models\User;
use App\Services\Contabil\ContabilPostingService;
use App\Services\Erp\DocumentoOrchestrator;
use App\Services\Erp\FormaPagamentoService;
use App\Services\Erp\LancamentoFinanceiroService;
use InvalidArgumentException;
use Tests\TestCase;

class DocumentoPagamentoSplitTest extends TestCase
{
    public function test_criar_rascunho_com_split_persiste_linhas_e_espelha_primeira(): void
    {
        [$empresa, $dinheiro, $pix, $produto] = $this->seedVendaBasica();

        $doc = app(DocumentoOrchestrator::class)->criarRascunho($empresa, [
            'tipo' => DocumentoComercial::TIPO_VENDA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFCE,
            'pagamentos' => [
                ['forma_pagamento_id' => $dinheiro->id, 'valor' => 40],
                ['forma_pagamento_id' => $pix->id, 'valor' => 60],
            ],
            'itens' => [[
                'produto_id' => $produto->id,
                'descricao' => $produto->descricao,
                'quantidade' => 1,
                'valor_unitario' => 100,
                'ncm' => '12345678',
                'cfop' => '5102',
                'csosn' => '102',
                'unidade' => 'UN',
            ]],
        ]);

        $this->assertSame(100.0, (float) $doc->valor_total);
        $this->assertSame($dinheiro->id, $doc->forma_pagamento_id);
        $this->assertSame('01', $doc->forma_pagamento);
        $this->assertCount(2, $doc->pagamentos);
        $this->assertEquals(40.0, (float) $doc->pagamentos[0]->valor);
        $this->assertEquals(60.0, (float) $doc->pagamentos[1]->valor);
    }

    public function test_legacy_forma_unica_gera_uma_linha_de_pagamento(): void
    {
        [$empresa, $dinheiro, , $produto] = $this->seedVendaBasica();

        $doc = app(DocumentoOrchestrator::class)->criarRascunho($empresa, [
            'tipo' => DocumentoComercial::TIPO_VENDA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFCE,
            'forma_pagamento_id' => $dinheiro->id,
            'itens' => [[
                'produto_id' => $produto->id,
                'descricao' => $produto->descricao,
                'quantidade' => 2,
                'valor_unitario' => 25.5,
                'ncm' => '12345678',
                'cfop' => '5102',
                'csosn' => '102',
                'unidade' => 'UN',
            ]],
        ]);

        $this->assertCount(1, $doc->pagamentos);
        $this->assertEquals(51.0, (float) $doc->pagamentos[0]->valor);
        $this->assertSame($dinheiro->id, $doc->pagamentos[0]->forma_pagamento_id);
    }

    public function test_soma_pagamentos_diferente_do_total_rejeita(): void
    {
        [$empresa, $dinheiro, $pix, $produto] = $this->seedVendaBasica();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('soma dos pagamentos');

        app(DocumentoOrchestrator::class)->criarRascunho($empresa, [
            'tipo' => DocumentoComercial::TIPO_VENDA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFCE,
            'pagamentos' => [
                ['forma_pagamento_id' => $dinheiro->id, 'valor' => 30],
                ['forma_pagamento_id' => $pix->id, 'valor' => 30],
            ],
            'itens' => [[
                'produto_id' => $produto->id,
                'descricao' => $produto->descricao,
                'quantidade' => 1,
                'valor_unitario' => 100,
                'ncm' => '12345678',
                'cfop' => '5102',
                'csosn' => '102',
                'unidade' => 'UN',
            ]],
        ]);
    }

    public function test_montar_payload_nfce_emite_varios_detpag(): void
    {
        [$empresa, $dinheiro, $pix, $produto] = $this->seedVendaBasica();
        $orch = app(DocumentoOrchestrator::class);

        $doc = $orch->criarRascunho($empresa, [
            'tipo' => DocumentoComercial::TIPO_VENDA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFCE,
            'pagamentos' => [
                ['forma_pagamento_id' => $dinheiro->id, 'valor' => 40, 'v_troco' => 5],
                ['forma_pagamento_id' => $pix->id, 'valor' => 60],
            ],
            'itens' => [[
                'produto_id' => $produto->id,
                'descricao' => $produto->descricao,
                'quantidade' => 1,
                'valor_unitario' => 100,
                'ncm' => '12345678',
                'cfop' => '5102',
                'csosn' => '102',
                'unidade' => 'UN',
            ]],
        ]);

        $payload = $orch->montarPayloadNfce($doc);
        $this->assertCount(2, $payload['pagamentos']);
        $this->assertSame('01', $payload['pagamentos'][0]['t_pag']);
        $this->assertEquals(40.0, $payload['pagamentos'][0]['v_pag']);
        $this->assertEquals(5.0, $payload['pagamentos'][0]['v_troco']);
        $this->assertSame('17', $payload['pagamentos'][1]['t_pag']);
        $this->assertEquals(60.0, $payload['pagamentos'][1]['v_pag']);
    }

    public function test_financeiro_gera_lancamentos_por_forma(): void
    {
        [$empresa, $dinheiro, $pix] = $this->seedVendaBasica();

        $credito = FormaPagamento::create([
            'empresa_id' => $empresa->id,
            'codigo' => '03',
            'nome' => 'Crédito',
            'ativo' => true,
            'tipo_liquidacao' => FormaPagamento::TIPO_PRAZO,
            'dias_recebimento' => 30,
            'parcelas' => 1,
            'juros_percentual' => 0,
            'gera_lancamento' => true,
            'conta_contabil_id' => $this->contaId($empresa->id, '1.1.03'),
        ]);

        $doc = DocumentoComercial::create([
            'empresa_id' => $empresa->id,
            'tipo' => DocumentoComercial::TIPO_VENDA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFCE,
            'status' => DocumentoComercial::STATUS_AUTORIZADO,
            'valor_total' => 100,
            'forma_pagamento' => '01',
            'forma_pagamento_id' => $dinheiro->id,
            'pago_avista' => true,
        ]);

        DocumentoPagamento::create([
            'documento_comercial_id' => $doc->id,
            'forma_pagamento_id' => $dinheiro->id,
            'valor' => 40,
            'ordem' => 1,
        ]);
        DocumentoPagamento::create([
            'documento_comercial_id' => $doc->id,
            'forma_pagamento_id' => $credito->id,
            'valor' => 60,
            'ordem' => 2,
        ]);

        $lancamentos = app(LancamentoFinanceiroService::class)->gerarDoDocumento($doc->fresh());

        $this->assertCount(2, $lancamentos);
        $this->assertSame($dinheiro->id, $lancamentos[0]->forma_pagamento_id);
        $this->assertSame(LancamentoFinanceiro::STATUS_PAGO, $lancamentos[0]->status);
        $this->assertEquals(40.0, (float) $lancamentos[0]->valor);
        $this->assertSame($credito->id, $lancamentos[1]->forma_pagamento_id);
        $this->assertSame(LancamentoFinanceiro::STATUS_ABERTO, $lancamentos[1]->status);
        $this->assertEquals(60.0, (float) $lancamentos[1]->valor);
        unset($pix);
    }

    public function test_contabil_venda_gera_debitos_por_forma(): void
    {
        [$empresa, $dinheiro, $pix] = $this->seedVendaBasica();

        $doc = DocumentoComercial::create([
            'empresa_id' => $empresa->id,
            'tipo' => DocumentoComercial::TIPO_VENDA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFCE,
            'status' => DocumentoComercial::STATUS_AUTORIZADO,
            'valor_total' => 100,
            'data_competencia' => '2026-07-20',
            'forma_pagamento_id' => $dinheiro->id,
            'forma_pagamento' => '01',
            'pago_avista' => true,
        ]);

        DocumentoPagamento::create([
            'documento_comercial_id' => $doc->id,
            'forma_pagamento_id' => $dinheiro->id,
            'valor' => 40,
            'ordem' => 1,
        ]);
        DocumentoPagamento::create([
            'documento_comercial_id' => $doc->id,
            'forma_pagamento_id' => $pix->id,
            'valor' => 60,
            'ordem' => 2,
        ]);

        app(ContabilPostingService::class)->fromDocumento($doc->fresh());

        $lanc = LancamentoContabil::first();
        $this->assertNotNull($lanc);

        $debitos = LancamentoContabilItem::where('lancamento_id', $lanc->id)->where('tipo', 'D')->get();
        $creditos = LancamentoContabilItem::where('lancamento_id', $lanc->id)->where('tipo', 'C')->get();

        $this->assertCount(2, $debitos);
        $this->assertCount(1, $creditos);
        $this->assertEquals(100.0, (float) $debitos->sum('valor'));
        $this->assertEquals(100.0, (float) $creditos->sum('valor'));

        $contaCaixa = $this->contaId($empresa->id, '1.1.01');
        $contaPix = $this->contaId($empresa->id, '1.1.02');
        $this->assertTrue($debitos->contains(fn ($i) => (int) $i->conta_id === $contaCaixa && (float) $i->valor === 40.0));
        $this->assertTrue($debitos->contains(fn ($i) => (int) $i->conta_id === $contaPix && (float) $i->valor === 60.0));
    }

    public function test_garantir_defaults_vincula_contas(): void
    {
        $empresa = $this->makeEmpresa();
        $formas = app(FormaPagamentoService::class)->garantirDefaults($empresa);

        $this->assertCount(4, $formas);
        foreach ($formas as $forma) {
            $this->assertNotNull($forma->conta_contabil_id);
        }

        $porCodigo = collect($formas)->keyBy('codigo');
        $this->assertSame(
            $this->contaId($empresa->id, '1.1.01'),
            (int) $porCodigo['01']->conta_contabil_id
        );
        $this->assertSame(
            $this->contaId($empresa->id, '1.1.02'),
            (int) $porCodigo['17']->conta_contabil_id
        );
    }

    /**
     * @return array{0: Empresa, 1: FormaPagamento, 2: FormaPagamento, 3: Produto}
     */
    private function seedVendaBasica(): array
    {
        $empresa = $this->makeEmpresa();
        app(FormaPagamentoService::class)->garantirDefaults($empresa);

        $dinheiro = FormaPagamento::where('empresa_id', $empresa->id)->where('codigo', '01')->firstOrFail();
        $pix = FormaPagamento::where('empresa_id', $empresa->id)->where('codigo', '17')->firstOrFail();

        $produto = Produto::create([
            'empresa_id' => $empresa->id,
            'descricao' => 'Produto split',
            'ncm' => '12345678',
            'cfop' => '5102',
            'csosn' => '102',
            'unidade' => 'UN',
            'preco_venda' => 100,
            'ativo' => true,
            'estoque_atual' => 10,
            'controla_estoque' => true,
        ]);

        return [$empresa, $dinheiro, $pix, $produto];
    }

    private function contaId(int $empresaId, string $codigo): int
    {
        return (int) ContaContabil::query()
            ->where('empresa_id', $empresaId)
            ->where('codigo', $codigo)
            ->value('id');
    }

    private function makeEmpresa(): Empresa
    {
        $user = User::factory()->create();

        return Empresa::create([
            'user_id' => $user->id,
            'cnpj' => '11222333000181',
            'razao_social' => 'SPLIT PAG TEST',
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
