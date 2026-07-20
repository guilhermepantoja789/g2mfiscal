<?php

namespace App\Core\FiscalEngine\Dto;

readonly class NfcePayment
{
    public function __construct(
        public string $tPag,
        public float $vPag,
        public ?float $vTroco = null,
    ) {}
}
