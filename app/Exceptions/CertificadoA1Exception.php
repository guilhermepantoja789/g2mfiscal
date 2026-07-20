<?php

namespace App\Exceptions;

use Exception;

class CertificadoA1Exception extends Exception
{
    public const EMPTY = 'empty';
    public const INVALID_FORMAT = 'invalid_format';
    public const WRONG_PASSWORD = 'wrong_password';
    public const LEGACY_UNSUPPORTED = 'legacy_unsupported';
    public const READ_FAILED = 'read_failed';

    public function __construct(
        string $message,
        public readonly string $codeKey = self::READ_FAILED,
        public readonly ?string $opensslError = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Campo de formulário sugerido para exibir o erro (upload).
     */
    public function formField(): string
    {
        return match ($this->codeKey) {
            self::WRONG_PASSWORD => 'senha',
            default => 'arquivo',
        };
    }
}
