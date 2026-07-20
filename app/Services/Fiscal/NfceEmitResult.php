<?php

namespace App\Services\Fiscal;

readonly class NfceEmitResult
{
    public function __construct(
        public bool $sucesso,
        public string $chave,
        public int $numero,
        public int $serie,
        public ?string $protocolo,
        public string $cStat,
        public string $xMotivo,
        public ?string $xmlEnviado,
        public ?string $xmlAutorizado,
        public ?string $qrCodeUrl,
    ) {}
}
