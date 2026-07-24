<?php

namespace App\Services\Fiscal;

readonly class NfceCancelResult
{
    public function __construct(
        public bool $sucesso,
        public string $cStat,
        public string $xMotivo,
        public ?string $protocolo = null,
        public ?string $xmlRetorno = null,
    ) {}
}
