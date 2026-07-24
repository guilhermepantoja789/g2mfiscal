<?php

namespace App\Services;

use App\Models\Empresa;
use Illuminate\Support\Facades\DB;

/**
 * Reserva nDPS único por empresa (série/CNPJ/município na SEFIN).
 */
class NfseDpsNumero
{
    public static function reservar(Empresa $empresa): int
    {
        return (int) DB::transaction(function () use ($empresa) {
            /** @var Empresa $locked */
            $locked = Empresa::query()->whereKey($empresa->id)->lockForUpdate()->firstOrFail();
            $proximo = ((int) $locked->nfse_dps_ultimo_numero) + 1;
            $locked->nfse_dps_ultimo_numero = $proximo;
            $locked->save();

            return $proximo;
        });
    }
}
