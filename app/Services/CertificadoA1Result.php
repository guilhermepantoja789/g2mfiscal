<?php

namespace App\Services;

use DateTimeImmutable;
use NFePHP\Common\Certificate;

class CertificadoA1Result
{
    /**
     * @param  array{cert: string, pkey: string, extracerts?: array}  $pkcs12
     * @param  array<string, mixed>  $x509
     */
    public function __construct(
        public readonly string $pfxContent,
        public readonly array $pkcs12,
        public readonly array $x509,
        public readonly DateTimeImmutable $validoAte,
        public readonly bool $convertedFromLegacy,
        public readonly Certificate $certificate,
        public readonly string $opensslVersion,
    ) {}

    public function toPem(): string
    {
        return $this->pkcs12['cert'] . "\n" . $this->pkcs12['pkey'];
    }

    public function commonName(): ?string
    {
        return $this->x509['subject']['CN'] ?? null;
    }

    public function isExpired(): bool
    {
        return $this->validoAte->getTimestamp() < time();
    }

    public function isNotYetValid(): bool
    {
        $from = $this->x509['validFrom_time_t'] ?? null;

        return is_int($from) && $from > time();
    }
}
