<?php

namespace App\Core\FiscalEngine\Exceptions;

class SefazRejectionException extends FiscalEngineException
{
    public function __construct(
        public readonly string $cStat,
        public readonly string $xMotivo,
        ?\Throwable $previous = null,
    ) {
        parent::__construct("SEFAZ [{$cStat}]: {$xMotivo}", 0, $previous);
    }

    public function isRetryable(): bool
    {
        return false;
    }
}
