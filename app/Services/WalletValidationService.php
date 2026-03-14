<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class WalletValidationService
{
    /**
     * USDT (TRC20) wallet format: TRON address starting with T, 34 characters.
     */
    private const TRON_PATTERN = '/^T[a-zA-Z0-9]{33}$/';

    private function getErrorMessage(): string
    {
        return __('messages.validation.usdt_address');
    }

    /**
     * Validate wallet address. Platform supports only TRON network.
     * Reject any address that does not match TRON format.
     */
    public function validate(string $walletAddress, string $currency, string $network): bool
    {
        $address = trim($walletAddress);

        return preg_match(self::TRON_PATTERN, $address) === 1;
    }

    /**
     * Validate and throw ValidationException if invalid.
     */
    public function validateOrFail(string $walletAddress, string $currency, string $network): void
    {
        if (!$this->validate($walletAddress, $currency, $network)) {
            throw ValidationException::withMessages([
                'wallet_address' => [$this->getErrorMessage()],
            ]);
        }
    }
}
