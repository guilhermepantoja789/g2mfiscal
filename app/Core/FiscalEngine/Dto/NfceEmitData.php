<?php

namespace App\Core\FiscalEngine\Dto;

readonly class NfceEmitData
{
    /**
     * @param  list<NfceItem>  $itens
     * @param  list<NfcePayment>  $pagamentos
     */
    public function __construct(
        public string $cnpj,
        public string $razaoSocial,
        public string $nomeFantasia,
        public string $ie,
        public int $crt,
        public string $logradouro,
        public string $numero,
        public string $bairro,
        public string $municipio,
        public string $uf,
        public string $cep,
        public string $cMun,
        public string $fone,
        public int $serie,
        public int $numeroNfce,
        public int $tpAmb,
        public int $tpEmis = 1,
        public ?string $destDoc = null,
        public ?string $destNome = null,
        public array $itens = [],
        public array $pagamentos = [],
        public string $naturezaOperacao = 'VENDA',
        public ?string $cNF = null,
        public ?\DateTimeInterface $dhEmi = null,
        public ?\DateTimeInterface $dhCont = null,
        public ?string $xJust = null,
    ) {}
}
