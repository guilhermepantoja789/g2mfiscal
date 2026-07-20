<?php

namespace App\Core\FiscalEngine\Dto;

readonly class NfceItem
{
    public function __construct(
        public string $descricao,
        public string $ncm,
        public string $cfop,
        public string $unidade,
        public float $quantidade,
        public float $valorUnitario,
        public string $csosn = '102',
        public string $pisCst = '49',
        public string $cofinsCst = '49',
        public ?string $cEAN = null,
        public ?string $cProd = null,
    ) {}

    public function valorTotal(): float
    {
        return round($this->quantidade * $this->valorUnitario, 2);
    }
}
