<?php

namespace App\Services\Fiscal;

use App\Core\FiscalEngine\Dto\NfceItem;
use App\Core\FiscalEngine\Dto\NfcePayment;

readonly class NfceEmitRequest
{
    /**
     * @param  list<NfceItem>  $itens
     * @param  list<NfcePayment>  $pagamentos
     */
    public function __construct(
        public array $itens,
        public array $pagamentos,
        public ?string $destDoc = null,
        public ?string $destNome = null,
        public string $naturezaOperacao = 'VENDA',
        public ?int $numeroOverride = null,
        public ?string $endpointProfile = null,
    ) {}
}
