<?php

namespace App\Services\Contabil;

use App\Models\ContaContabil;
use App\Models\DocumentoComercial;
use App\Models\Empresa;
use App\Models\LancamentoContabil;
use App\Models\LancamentoContabilItem;
use App\Models\MapeamentoContabil;
use App\Models\PeriodoContabil;
use App\Models\PlanoContas;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Postagem contábil automática a partir de DocumentoComercial (fase 2B).
 */
class ContabilPostingService
{
    public const ORIGEM_TIPO_DOCUMENTO = 'documento_comercial';

    public const ORIGEM_TIPO_ESTORNO = 'documento_comercial_estorno';

    public const MAP_NFCE_VENDA = 'nfce_venda';

    public const MAP_NFSE = 'nfse_iss';

    public const MAP_NFE_COMPRA = 'nfe_compra';

    public function __construct(
        private CompetenciaResolver $competencia,
    ) {}

    public function fromDocumento(DocumentoComercial $documento): void
    {
        if ($this->jaPostado($documento->id, self::ORIGEM_TIPO_DOCUMENTO)) {
            return;
        }

        $origemMap = $this->origemMapeamento($documento);
        if ($origemMap === null) {
            return;
        }

        $this->garantirPlanoPadrao(Empresa::findOrFail($documento->empresa_id));

        $mapa = $this->resolverMapaDebitoCredito((int) $documento->empresa_id, $origemMap);
        if ($mapa === null) {
            return;
        }

        $valor = round((float) $documento->valor_total, 2);
        if ($valor <= 0) {
            return;
        }

        $data = $this->dataCompetenciaDocumento($documento);

        DB::transaction(function () use ($documento, $mapa, $valor, $data, $origemMap) {
            $periodo = $this->garantirPeriodoAberto((int) $documento->empresa_id, $data->format('Y-m'));

            $lancamento = LancamentoContabil::create([
                'empresa_id' => $documento->empresa_id,
                'periodo_id' => $periodo->id,
                'data' => $data->toDateString(),
                'historico' => sprintf(
                    'Doc #%d (%s / %s)',
                    $documento->id,
                    $documento->canal_fiscal,
                    $origemMap
                ),
                'origem_tipo' => self::ORIGEM_TIPO_DOCUMENTO,
                'origem_id' => $documento->id,
                'status' => LancamentoContabil::STATUS_LANCADO,
            ]);

            LancamentoContabilItem::create([
                'lancamento_id' => $lancamento->id,
                'conta_id' => $mapa['debito']->id,
                'tipo' => 'D',
                'valor' => $valor,
            ]);

            LancamentoContabilItem::create([
                'lancamento_id' => $lancamento->id,
                'conta_id' => $mapa['credito']->id,
                'tipo' => 'C',
                'valor' => $valor,
            ]);
        });
    }

    public function reverterDocumento(DocumentoComercial $documento): void
    {
        if ($this->jaPostado($documento->id, self::ORIGEM_TIPO_ESTORNO)) {
            return;
        }

        $original = LancamentoContabil::query()
            ->where('origem_tipo', self::ORIGEM_TIPO_DOCUMENTO)
            ->where('origem_id', $documento->id)
            ->where('status', LancamentoContabil::STATUS_LANCADO)
            ->with('itens')
            ->first();

        if (! $original || $original->itens->isEmpty()) {
            return;
        }

        $data = now()->startOfDay();

        DB::transaction(function () use ($documento, $original, $data) {
            $periodo = $this->garantirPeriodoAberto((int) $documento->empresa_id, $data->format('Y-m'));

            $estorno = LancamentoContabil::create([
                'empresa_id' => $documento->empresa_id,
                'periodo_id' => $periodo->id,
                'data' => $data->toDateString(),
                'historico' => sprintf('Estorno doc #%d (ref. lanç. #%d)', $documento->id, $original->id),
                'origem_tipo' => self::ORIGEM_TIPO_ESTORNO,
                'origem_id' => $documento->id,
                'status' => LancamentoContabil::STATUS_LANCADO,
            ]);

            foreach ($original->itens as $item) {
                LancamentoContabilItem::create([
                    'lancamento_id' => $estorno->id,
                    'conta_id' => $item->conta_id,
                    'tipo' => $item->tipo === 'D' ? 'C' : 'D',
                    'valor' => $item->valor,
                ]);
            }
        });
    }

    /**
     * Garante plano mínimo + contas + mapeamentos D/C por origem fiscal.
     */
    public function garantirPlanoPadrao(Empresa $empresa): PlanoContas
    {
        $plano = PlanoContas::query()
            ->where('empresa_id', $empresa->id)
            ->where('codigo', 'PADRAO')
            ->first();

        if (! $plano) {
            $plano = PlanoContas::create([
                'empresa_id' => $empresa->id,
                'codigo' => 'PADRAO',
                'nome' => 'Plano gerencial padrão',
                'tipo' => 'patrimonio',
                'natureza' => 'C',
                'ativo' => true,
            ]);
        }

        $contas = [
            '1.1.01' => ['nome' => 'Caixa / Clientes', 'tipo' => 'ativo', 'natureza' => 'D'],
            '1.2.01' => ['nome' => 'Estoques', 'tipo' => 'ativo', 'natureza' => 'D'],
            '2.1.01' => ['nome' => 'Fornecedores', 'tipo' => 'passivo', 'natureza' => 'C'],
            '3.1.01' => ['nome' => 'Receita de vendas (produtos)', 'tipo' => 'receita', 'natureza' => 'C'],
            '3.1.02' => ['nome' => 'Receita de serviços', 'tipo' => 'receita', 'natureza' => 'C'],
            '4.1.01' => ['nome' => 'CMV / Custo mercadorias', 'tipo' => 'despesa', 'natureza' => 'D'],
        ];

        $ids = [];
        foreach ($contas as $codigo => $meta) {
            $conta = ContaContabil::query()
                ->where('empresa_id', $empresa->id)
                ->where('codigo', $codigo)
                ->first();

            if (! $conta) {
                $conta = ContaContabil::create([
                    'empresa_id' => $empresa->id,
                    'plano_id' => $plano->id,
                    'codigo' => $codigo,
                    'nome' => $meta['nome'],
                    'tipo' => $meta['tipo'],
                    'natureza' => $meta['natureza'],
                    'ativo' => true,
                ]);
            }

            $ids[$codigo] = $conta;
        }

        $this->garantirMapeamento($empresa->id, self::MAP_NFCE_VENDA, $ids['1.1.01'], 'D');
        $this->garantirMapeamento($empresa->id, self::MAP_NFCE_VENDA, $ids['3.1.01'], 'C');

        $this->garantirMapeamento($empresa->id, self::MAP_NFSE, $ids['1.1.01'], 'D');
        $this->garantirMapeamento($empresa->id, self::MAP_NFSE, $ids['3.1.02'], 'C');

        $this->garantirMapeamento($empresa->id, self::MAP_NFE_COMPRA, $ids['1.2.01'], 'D');
        $this->garantirMapeamento($empresa->id, self::MAP_NFE_COMPRA, $ids['2.1.01'], 'C');

        return $plano->fresh();
    }

    private function garantirMapeamento(int $empresaId, string $origem, ContaContabil $conta, string $papel): void
    {
        $existente = MapeamentoContabil::query()
            ->where('empresa_id', $empresaId)
            ->where('origem', $origem)
            ->where('conta_id', $conta->id)
            ->first();

        if ($existente) {
            $meta = $existente->metadados ?? [];
            if (($meta['papel'] ?? null) !== $papel) {
                $existente->update(['metadados' => array_merge($meta, ['papel' => $papel])]);
            }

            return;
        }

        MapeamentoContabil::create([
            'empresa_id' => $empresaId,
            'origem' => $origem,
            'conta_id' => $conta->id,
            'metadados' => ['papel' => $papel],
        ]);
    }

    /**
     * @return array{debito: ContaContabil, credito: ContaContabil}|null
     */
    private function resolverMapaDebitoCredito(int $empresaId, string $origem): ?array
    {
        $mapas = MapeamentoContabil::query()
            ->where('empresa_id', $empresaId)
            ->where('origem', $origem)
            ->with('conta')
            ->get();

        $debito = null;
        $credito = null;

        foreach ($mapas as $mapa) {
            $papel = strtoupper((string) ($mapa->metadados['papel'] ?? ''));
            if ($papel === 'D' && $mapa->conta) {
                $debito = $mapa->conta;
            }
            if ($papel === 'C' && $mapa->conta) {
                $credito = $mapa->conta;
            }
        }

        if (! $debito || ! $credito) {
            return null;
        }

        return ['debito' => $debito, 'credito' => $credito];
    }

    private function origemMapeamento(DocumentoComercial $documento): ?string
    {
        return match ($documento->canal_fiscal) {
            DocumentoComercial::CANAL_NFCE => self::MAP_NFCE_VENDA,
            DocumentoComercial::CANAL_NFSE => self::MAP_NFSE,
            DocumentoComercial::CANAL_NFE_ENTRADA => self::MAP_NFE_COMPRA,
            default => null,
        };
    }

    private function jaPostado(int $documentoId, string $origemTipo): bool
    {
        return LancamentoContabil::query()
            ->where('origem_tipo', $origemTipo)
            ->where('origem_id', $documentoId)
            ->where('status', LancamentoContabil::STATUS_LANCADO)
            ->exists();
    }

    private function dataCompetenciaDocumento(DocumentoComercial $documento): \Carbon\Carbon
    {
        $documento->loadMissing(['nfce', 'notaFiscal']);

        if ($documento->data_competencia) {
            return $documento->data_competencia->copy()->startOfDay();
        }

        if ($documento->nfce) {
            if ($documento->nfce->data_emissao) {
                return $documento->nfce->data_emissao->copy()->startOfDay();
            }
            $fromXml = $this->competencia->dataDeXml(
                $documento->nfce->xml_autorizado ?: $documento->nfce->xml_enviado
            );
            if ($fromXml) {
                return $fromXml;
            }
        }

        if ($documento->notaFiscal?->emissao) {
            return $documento->notaFiscal->emissao->copy()->startOfDay();
        }

        if ($documento->xml_nfe) {
            $fromXml = $this->competencia->dataDeXml($documento->xml_nfe);
            if ($fromXml) {
                return $fromXml;
            }
        }

        return now()->startOfDay();
    }

    private function garantirPeriodoAberto(int $empresaId, string $competencia): PeriodoContabil
    {
        $periodo = PeriodoContabil::query()
            ->where('empresa_id', $empresaId)
            ->where('competencia', $competencia)
            ->first();

        if ($periodo) {
            if ($periodo->status === PeriodoContabil::STATUS_FECHADO) {
                throw new RuntimeException("Período contábil {$competencia} está fechado.");
            }

            return $periodo;
        }

        return PeriodoContabil::create([
            'empresa_id' => $empresaId,
            'competencia' => $competencia,
            'status' => PeriodoContabil::STATUS_ABERTO,
        ]);
    }
}
