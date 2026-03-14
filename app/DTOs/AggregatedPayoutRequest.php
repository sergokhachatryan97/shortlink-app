<?php

namespace App\DTOs;

/**
 * Represents an aggregated payout request for multiple commission records.
 * Used for daily batch payouts. Has the same shape as PartnerCommissionPayout
 * for provider compatibility.
 */
readonly class AggregatedPayoutRequest
{
    public function __construct(
        public float $commission_amount,
        public string $currency,
        public ?string $network,
        public string $wallet_address,
        public string $provider,
        public string $batchIdentifier,
        public array $commissionIds = [],
    ) {}
}
