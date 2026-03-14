<?php

namespace App\Contracts;

readonly class PartnerPayoutResult
{
    public function __construct(
        public bool $success,
        public ?string $providerTransactionId = null,
        public ?string $errorMessage = null,
    ) {}
}
