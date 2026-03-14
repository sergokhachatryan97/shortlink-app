<?php

namespace App\Services;

use App\DTOs\AggregatedPayoutRequest;
use App\Models\PartnerCommissionPayout;
use App\Services\PartnerPayout\PartnerPayoutProviderResolver;
use Illuminate\Support\Facades\Log;

class PartnerPayoutService
{
    public function __construct(
        protected PartnerPayoutProviderResolver $resolver
    ) {}

    /**
     * Send an aggregated batch payout. One provider call for the total amount.
     * On success, all commission records are marked paid. On failure, all marked failed.
     *
     * @param array<int, PartnerCommissionPayout> $commissions Non-empty list of pending commissions in same group
     */
    public function sendBatchPayout(array $commissions): bool
    {
        if (empty($commissions)) {
            return false;
        }

        $first = $commissions[array_key_first($commissions)];
        $ids = array_map(fn (PartnerCommissionPayout $p) => $p->id, $commissions);
        $totalAmount = array_sum(array_map(fn (PartnerCommissionPayout $p) => (float) $p->commission_amount, $commissions));
        $batchIdentifier = 'batch-' . implode(',', $ids);

        if (empty(trim($first->wallet_address ?? ''))) {
            Log::error('PartnerPayoutService: missing wallet address', ['batch' => $batchIdentifier]);
            $this->markBatchFailed($ids, 'Missing wallet address');
            return false;
        }

        if ($totalAmount <= 0) {
            Log::warning('PartnerPayoutService: batch total <= 0', ['batch' => $batchIdentifier]);
            return false;
        }

        try {
            $provider = $this->resolver->resolve($first->provider);
        } catch (\InvalidArgumentException $e) {
            Log::error('PartnerPayoutService: unsupported provider', [
                'batch' => $batchIdentifier,
                'provider' => $first->provider,
                'error' => $e->getMessage(),
            ]);
            $this->markBatchFailed($ids, $e->getMessage());
            return false;
        }

        $request = new AggregatedPayoutRequest(
            commission_amount: round($totalAmount, 2),
            currency: $first->currency ?? 'USDT',
            network: $first->network,
            wallet_address: $first->wallet_address,
            provider: $first->provider,
            batchIdentifier: $batchIdentifier,
            commissionIds: $ids,
        );

        $result = $provider->sendPayout($request);

        if ($result->success) {
            PartnerCommissionPayout::whereIn('id', $ids)->update([
                'status' => PartnerCommissionPayout::STATUS_PAID,
                'provider_transaction_id' => $result->providerTransactionId,
                'error_message' => null,
            ]);
            Log::info('PartnerPayoutService: batch paid', [
                'batch' => $batchIdentifier,
                'amount' => $totalAmount,
                'provider_tx' => $result->providerTransactionId,
            ]);
            return true;
        }

        $this->markBatchFailed($ids, $result->errorMessage ?? 'Payout failed');
        return false;
    }

    private function markBatchFailed(array $ids, string $errorMessage): void
    {
        PartnerCommissionPayout::whereIn('id', $ids)->update([
            'status' => PartnerCommissionPayout::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }
}
