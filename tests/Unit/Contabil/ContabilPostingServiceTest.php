<?php

namespace Tests\Unit\Contabil;

use App\Models\DocumentoComercial;
use App\Models\Empresa;
use App\Models\LancamentoContabil;
use App\Models\LancamentoContabilItem;
use App\Models\User;
use App\Services\Contabil\CompetenciaResolver;
use App\Services\Contabil\ContabilPostingService;
use App\Services\Contabil\ContabilRelatorioService;
use Carbon\Carbon;
use Tests\TestCase;

class ContabilPostingServiceTest extends TestCase
{
    public function test_from_documento_gera_partida_dobrada_e_reverter_estorna(): void
    {
        $empresa = $this->makeEmpresa();

        $doc = DocumentoComercial::create([
            'empresa_id' => $empresa->id,
            'tipo' => DocumentoComercial::TIPO_VENDA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFCE,
            'status' => DocumentoComercial::STATUS_AUTORIZADO,
            'valor_total' => 100,
            'data_competencia' => '2026-07-15',
            'pago_avista' => true,
        ]);

        $service = app(ContabilPostingService::class);
        $service->fromDocumento($doc);

        $this->assertDatabaseCount('lancamentos_contabeis', 1);
        $this->assertDatabaseCount('lancamento_contabil_itens', 2);

        $lanc = LancamentoContabil::first();
        $this->assertSame(ContabilPostingService::ORIGEM_TIPO_DOCUMENTO, $lanc->origem_tipo);
        $this->assertSame($doc->id, $lanc->origem_id);
        $this->assertSame('2026-07-15', $lanc->data->format('Y-m-d'));

        $debitos = LancamentoContabilItem::where('lancamento_id', $lanc->id)->where('tipo', 'D')->sum('valor');
        $creditos = LancamentoContabilItem::where('lancamento_id', $lanc->id)->where('tipo', 'C')->sum('valor');
        $this->assertEquals(100.0, (float) $debitos);
        $this->assertEquals(100.0, (float) $creditos);

        // idempotente
        $service->fromDocumento($doc);
        $this->assertDatabaseCount('lancamentos_contabeis', 1);

        $service->reverterDocumento($doc);
        $this->assertDatabaseCount('lancamentos_contabeis', 2);

        $estorno = LancamentoContabil::where('origem_tipo', ContabilPostingService::ORIGEM_TIPO_ESTORNO)->first();
        $this->assertNotNull($estorno);
        $this->assertEquals(
            100.0,
            (float) LancamentoContabilItem::where('lancamento_id', $estorno->id)->where('tipo', 'C')->sum('valor')
        );

        // reverter idempotente
        $service->reverterDocumento($doc);
        $this->assertDatabaseCount('lancamentos_contabeis', 2);
    }

    public function test_dre_e_balanco_apos_postagem(): void
    {
        $empresa = $this->makeEmpresa();
        $doc = DocumentoComercial::create([
            'empresa_id' => $empresa->id,
            'tipo' => DocumentoComercial::TIPO_VENDA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFCE,
            'status' => DocumentoComercial::STATUS_AUTORIZADO,
            'valor_total' => 250,
            'data_competencia' => now()->toDateString(),
            'pago_avista' => true,
        ]);

        app(ContabilPostingService::class)->fromDocumento($doc);

        $rel = app(ContabilRelatorioService::class);
        $dre = $rel->dre($empresa->id, now()->startOfMonth(), now()->endOfMonth());
        $this->assertEquals(250.0, $dre['receitas']);
        $this->assertEquals(250.0, $dre['resultado']);

        $balanco = $rel->balanco($empresa->id, now()->endOfDay());
        $this->assertEquals(250.0, $balanco['ativo']);
    }

    public function test_competencia_resolver_extrai_dhemi(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<nfeProc xmlns="http://www.portalfiscal.inf.br/nfe">
  <NFe><infNFe>
    <ide><dhEmi>2026-06-10T14:30:00-04:00</dhEmi></ide>
  </infNFe></NFe>
</nfeProc>
XML;

        $data = app(CompetenciaResolver::class)->dataDeXml($xml);
        $this->assertInstanceOf(Carbon::class, $data);
        $this->assertSame('2026-06-10', $data->format('Y-m-d'));
    }

    private function makeEmpresa(): Empresa
    {
        $user = User::factory()->create();

        return Empresa::create([
            'user_id' => $user->id,
            'cnpj' => '99888777000166',
            'razao_social' => 'CONTABIL POSTING',
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
