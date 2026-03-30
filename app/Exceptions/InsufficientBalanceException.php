<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientBalanceException extends RuntimeException
{
    public function __construct(
        public readonly string $modelClass,
        public readonly int $modelId,
        public readonly string $currentBalance,
        public readonly string $requiredAmount,
        string $message = 'Insufficient balance for this operation.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
