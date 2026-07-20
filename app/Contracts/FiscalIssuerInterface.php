<?php

namespace App\Contracts;

use App\Models\Empresa;
use App\Models\Nfce;
use App\Services\Fiscal\NfceEmitRequest;
use App\Services\Fiscal\NfceEmitResult;

interface FiscalIssuerInterface
{
    public function emit(Empresa $empresa, NfceEmitRequest $request, ?Nfce $nfce = null): NfceEmitResult;

    /**
     * @return array{cStat: string, xMotivo: string, xml_retorno: string}
     */
    public function statusServico(Empresa $empresa, ?string $endpointProfile = null): array;
}
