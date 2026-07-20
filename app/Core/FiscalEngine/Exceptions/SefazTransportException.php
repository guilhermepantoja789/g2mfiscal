<?php

namespace App\Core\FiscalEngine\Exceptions;

class SefazTransportException extends FiscalEngineException
{
    public function isRetryable(): bool
    {
        return true;
    }
}
