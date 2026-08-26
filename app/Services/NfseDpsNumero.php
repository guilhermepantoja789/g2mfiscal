<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\NotaFiscal;
use Illuminate\Support\Facades\DB;

/**
 * Reserva nDPS único por empresa (série/CNPJ/município na SEFIN).
 *
 * O contador local pode ficar atrás da SEFIN (notas antigas usavam id como nDPS).
 * Por isso o próximo número considera o maior já persistido, não só o campo da empresa.
 */
class NfseDpsNumero
{
    public static function reservar(Empresa $empresa): int
    {
        return (int) DB::transaction(function () use ($empresa) {
            /** @var Empresa $locked */
            $locked = Empresa::query()->whereKey($empresa->id)->lockForUpdate()->firstOrFail();
            $proximo = self::proximoLivre($locked);
            $locked->nfse_dps_ultimo_numero = $proximo;
            $locked->save();

            return $proximo;
        });
    }

    /**
     * Maior nDPS já usado localmente (contador + notas) — sem carregar XML.
     */
    public static function maiorUsado(Empresa $empresa): int
    {
        $fromCounter = (int) $empresa->nfse_dps_ultimo_numero;
        $fromDps = (int) NotaFiscal::query()
            ->where('empresa_id', $empresa->id)
            ->max('numero_dps');
        $fromLegacyIds = (int) NotaFiscal::query()
            ->where('empresa_id', $empresa->id)
            ->whereNull('numero_dps')
            ->where(function ($q) {
                $q->where('status', 'autorizada')
                    ->orWhereNotNull('xml_enviado');
            })
            ->max('id');

        return max($fromCounter, $fromDps, $fromLegacyIds);
    }

    public static function proximoLivre(Empresa $empresa): int
    {
        return self::maiorUsado($empresa) + 1;
    }
}
