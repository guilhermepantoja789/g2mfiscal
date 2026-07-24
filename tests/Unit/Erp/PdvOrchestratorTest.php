<?php

namespace Tests\Unit\Erp;

use App\Jobs\EmitirNfceJob;
use App\Models\Cliente;
use App\Models\DocumentoComercial;
use App\Models\Empresa;
use App\Models\FormaPagamento;
use App\Models\Produto;
use App\Models\User;
use App\Services\Erp\DocumentoOrchestrator;
use App\Services\Erp\FormaPagamentoService;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PdvOrchestratorTest extends TestCase
{

    public function test_pdv_fluxo_cria_documento_e_dispara_job_nfce(): void
    {
        Queue::fake();

        $empresa = $this->makeEmpresa();
        app(FormaPagamentoService::class)->garantirDefaults($empresa);
        $forma = FormaPagamento::where('empresa_id', $empresa->id)->where('codigo', '01')->firstOrFail();

        $produto = Produto::create([
            'empresa_id' => $empresa->id,
            'descricao' => 'Água',
            'ean' => '7891000100103',
            'ncm' => '22011000',
            'cfop' => '5102',
            'csosn' => '102',
            'unidade' => 'UN',
            'preco_venda' => 3.5,
            'estoque_atual' => 50,
            'controla_estoque' => true,
            'ativo' => true,
        ]);

        $orch = app(DocumentoOrchestrator::class);
        $doc = $orch->criarRascunho($empresa, [
            'tipo' => DocumentoComercial::TIPO_VENDA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFCE,
            'forma_pagamento_id' => $forma->id,
            'itens' => [[
                'produto_id' => $produto->id,
                'descricao' => $produto->descricao,
                'ncm' => $produto->ncm,
                'cfop' => $produto->cfop,
                'csosn' => $produto->csosn,
                'unidade' => $produto->unidade,
                'ean' => $produto->ean,
                'quantidade' => 2,
                'valor_unitario' => 3.5,
            ]],
        ]);

        $this->assertSame($forma->id, $doc->forma_pagamento_id);
        $this->assertSame('01', $doc->forma_pagamento);
        $this->assertTrue($doc->pago_avista);
        $this->assertEquals(7.0, (float) $doc->valor_total);

        $doc = $orch->confirmar($doc);

        $this->assertSame(DocumentoComercial::STATUS_PROCESSANDO_FISCAL, $doc->status);
        $this->assertNotNull($doc->nfce);
        Queue::assertPushed(EmitirNfceJob::class);
    }

    public function test_dest_livre_no_payload_sem_cliente(): void
    {
        Queue::fake();

        $empresa = $this->makeEmpresa();
        app(FormaPagamentoService::class)->garantirDefaults($empresa);
        $forma = FormaPagamento::where('empresa_id', $empresa->id)->where('codigo', '01')->firstOrFail();
        $produto = $this->makeProduto($empresa);

        $orch = app(DocumentoOrchestrator::class);
        $doc = $orch->criarRascunho($empresa, [
            'tipo' => DocumentoComercial::TIPO_VENDA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFCE,
            'forma_pagamento_id' => $forma->id,
            'dest_doc' => '529.982.247-25',
            'dest_nome' => 'CONSUMIDOR PDV',
            'itens' => [[
                'produto_id' => $produto->id,
                'descricao' => $produto->descricao,
                'ncm' => $produto->ncm,
                'cfop' => $produto->cfop,
                'csosn' => $produto->csosn,
                'unidade' => $produto->unidade,
                'ean' => $produto->ean,
                'quantidade' => 1,
                'valor_unitario' => 3.5,
            ]],
        ]);

        $this->assertNull($doc->cliente_id);

        $payload = $orch->montarPayloadNfce($doc);
        $this->assertSame('52998224725', $payload['dest_doc']);
        $this->assertSame('CONSUMIDOR PDV', $payload['dest_nome']);

        $doc = $orch->confirmar($doc);
        $this->assertSame('52998224725', $doc->nfce->destinatario_doc);
        $this->assertSame('CONSUMIDOR PDV', $doc->nfce->destinatario_nome);
    }

    public function test_cliente_id_preenche_dest_quando_sem_dest_explicito(): void
    {
        Queue::fake();

        $empresa = $this->makeEmpresa();
        app(FormaPagamentoService::class)->garantirDefaults($empresa);
        $forma = FormaPagamento::where('empresa_id', $empresa->id)->where('codigo', '01')->firstOrFail();
        $produto = $this->makeProduto($empresa);

        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'razao_social' => 'CLIENTE CADASTRO',
            'cnpj' => '39053344705',
        ]);

        $orch = app(DocumentoOrchestrator::class);
        $doc = $orch->criarRascunho($empresa, [
            'tipo' => DocumentoComercial::TIPO_VENDA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFCE,
            'forma_pagamento_id' => $forma->id,
            'cliente_id' => $cliente->id,
            'itens' => [[
                'produto_id' => $produto->id,
                'descricao' => $produto->descricao,
                'ncm' => $produto->ncm,
                'cfop' => $produto->cfop,
                'csosn' => $produto->csosn,
                'unidade' => $produto->unidade,
                'ean' => $produto->ean,
                'quantidade' => 1,
                'valor_unitario' => 3.5,
            ]],
        ]);

        $payload = $orch->montarPayloadNfce($doc);
        $this->assertSame('39053344705', $payload['dest_doc']);
        $this->assertSame('CLIENTE CADASTRO', $payload['dest_nome']);
    }

    public function test_dest_explicito_tem_prioridade_sobre_cliente(): void
    {
        Queue::fake();

        $empresa = $this->makeEmpresa();
        app(FormaPagamentoService::class)->garantirDefaults($empresa);
        $forma = FormaPagamento::where('empresa_id', $empresa->id)->where('codigo', '01')->firstOrFail();
        $produto = $this->makeProduto($empresa);

        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'razao_social' => 'CLIENTE CADASTRO',
            'cnpj' => '39053344705',
        ]);

        $orch = app(DocumentoOrchestrator::class);
        $doc = $orch->criarRascunho($empresa, [
            'tipo' => DocumentoComercial::TIPO_VENDA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFCE,
            'forma_pagamento_id' => $forma->id,
            'cliente_id' => $cliente->id,
            'dest_doc' => '52998224725',
            'dest_nome' => 'NOME LIVRE',
            'itens' => [[
                'produto_id' => $produto->id,
                'descricao' => $produto->descricao,
                'ncm' => $produto->ncm,
                'cfop' => $produto->cfop,
                'csosn' => $produto->csosn,
                'unidade' => $produto->unidade,
                'ean' => $produto->ean,
                'quantidade' => 1,
                'valor_unitario' => 3.5,
            ]],
        ]);

        $payload = $orch->montarPayloadNfce($doc);
        $this->assertSame('52998224725', $payload['dest_doc']);
        $this->assertSame('NOME LIVRE', $payload['dest_nome']);
        $this->assertSame($cliente->id, $doc->cliente_id);
    }

    private function makeProduto(Empresa $empresa): Produto
    {
        return Produto::create([
            'empresa_id' => $empresa->id,
            'descricao' => 'Água',
            'ean' => '7891000100103',
            'ncm' => '22011000',
            'cfop' => '5102',
            'csosn' => '102',
            'unidade' => 'UN',
            'preco_venda' => 3.5,
            'estoque_atual' => 50,
            'controla_estoque' => true,
            'ativo' => true,
        ]);
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
