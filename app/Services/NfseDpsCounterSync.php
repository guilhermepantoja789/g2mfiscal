<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\NotaFiscal;
use Illuminate\Database\Eloquent\Builder;

/**
 * Backfill de nDPS e sincronização do contador — queries agregadas, sem carregar XML.
 * Pensado para hospedagem compartilhada (pouca memória / timeout curto).
 */
class NfseDpsCounterSync
{
    /**
     * @return array{backfilled: int, counters: list<array{empresa_id: int, razao_social: string, antes: int, depois: int}>}
     */
    public function sync(?int $empresaId = null, bool $dryRun = false): array
    {
        $backfilled = $this->backfillNumeroDps($empresaId, $dryRun);
        $counters = $this->syncCounters($empresaId, $dryRun);

        return [
            'backfilled' => $backfilled,
            'counters' => $counters,
        ];
    }

    public function backfillNumeroDps(?int $empresaId, bool $dryRun): int
    {
        $ids = $this->notasSemDpsEnviadas($empresaId)->orderBy('id')->pluck('id');
        $count = $ids->count();

        if ($dryRun || $count === 0) {
            return $count;
        }

        foreach ($ids as $id) {
            NotaFiscal::query()->whereKey($id)->update(['numero_dps' => (int) $id]);
        }

        return $count;
    }

    /**
     * @return list<array{empresa_id: int, razao_social: string, antes: int, depois: int}>
     */
    public function syncCounters(?int $empresaId, bool $dryRun): array
    {
        $empresas = Empresa::query()
            ->when($empresaId, fn ($q) => $q->whereKey($empresaId))
            ->orderBy('id')
            ->get(['id', 'razao_social', 'nfse_dps_ultimo_numero']);

        $rows = [];

        foreach ($empresas as $empresa) {
            $antes = (int) $empresa->nfse_dps_ultimo_numero;
            $depois = NfseDpsNumero::maiorUsado($empresa);

            if (! $dryRun && $depois > $antes) {
                Empresa::query()->whereKey($empresa->id)->update([
                    'nfse_dps_ultimo_numero' => $depois,
                ]);
            }

            $rows[] = [
                'empresa_id' => (int) $empresa->id,
                'razao_social' => (string) $empresa->razao_social,
                'antes' => $antes,
                'depois' => max($antes, $depois),
            ];
        }

        return $rows;
    }

    private function notasSemDpsEnviadas(?int $empresaId): Builder
    {
        return NotaFiscal::query()
            ->whereNull('numero_dps')
            ->where(function ($q) {
                $q->where('status', 'autorizada')
                    ->orWhereNotNull('xml_enviado');
            })
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId));
    }
}
