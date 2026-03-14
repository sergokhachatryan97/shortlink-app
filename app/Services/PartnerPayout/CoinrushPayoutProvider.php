<?php

namespace App\Services\PartnerPayout;

use App\Contracts\PartnerPayoutProviderInterface;
use App\Contracts\PartnerPayoutResult;
use App\Models\PartnerCommissionPayout;
use Illuminate\Support\Facades\Log;

class CoinrushPayoutProvider implements PartnerPayoutProviderInterface
{
    public function getProviderKey(): string
    {
        return 'coinrush';
    }

    public function sendPayout(PartnerCommissionPayout|\App\DTOs\AggregatedPayoutRequest $payout): PartnerPayoutResult
    {
        $apiUrl = config('services.coinrush.api_url');
        $apiKey = config('services.coinrush.payout_api_key') ?? config('services.coinrush.store_key');

        if (!$apiKey) {
            Log::error('CoinrushPayoutProvider: API key not configured');
            return new PartnerPayoutResult(false, null, 'Coinrush payout not configured');
        }

        // TODO: Integrate with CoinRush payout/withdrawal API when documentation is available.
        // The current CoinRush integration uses the frontend Tron widget for incoming payments.
        // For outgoing payouts, a separate merchant/partner payout API may be required.
        // See CoinRush documentation for withdrawal endpoints.
        // Example structure (adapt to actual API):
        // $response = Http::withHeaders([...])->post($apiUrl . '/payout', [...]);
        // if ($response->successful()) {
        //     return new PartnerPayoutResult(true, $data['tx_hash'] ?? null);
        // }

        $payoutId = $payout instanceof \App\DTOs\AggregatedPayoutRequest ? $payout->batchIdentifier : $payout->id;
        Log::warning('CoinrushPayoutProvider: payout API integration pending', [
            'payout_id' => $payoutId,
            'amount' => $payout->commission_amount,
            'wallet' => $payout->wallet_address,
        ]);

        return new PartnerPayoutResult(false, null, 'Coinrush payout API not integrated yet');
    }
}
