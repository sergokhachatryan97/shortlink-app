<?php

namespace App\Contracts;

use App\Models\PartnerCommissionPayout;
use App\DTOs\AggregatedPayoutRequest;

interface PartnerPayoutProviderInterface
{
    public function getProviderKey(): string;

    /**
     * @param PartnerCommissionPayout|AggregatedPayoutRequest $payout Single record or aggregated batch
     */
    public function sendPayout(PartnerCommissionPayout|AggregatedPayoutRequest $payout): PartnerPayoutResult;
}
