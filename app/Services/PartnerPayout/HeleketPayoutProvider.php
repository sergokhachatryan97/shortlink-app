<?php

namespace App\Services\PartnerPayout;

use App\Contracts\PartnerPayoutProviderInterface;
use App\Contracts\PartnerPayoutResult;
use App\DTOs\AggregatedPayoutRequest;
use App\Models\PartnerCommissionPayout;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class HeleketPayoutProvider implements PartnerPayoutProviderInterface
{
    public function getProviderKey(): string
    {
        return 'heleket';
    }

    public function sendPayout(PartnerCommissionPayout|AggregatedPayoutRequest $payout): PartnerPayoutResult
    {
        $baseUrl = rtrim(config('services.heleket.base', 'https://api.heleket.com'), '/');
        $merchantUuid = config('services.heleket.merchant_uuid');
        $apiKey = config('services.heleket.payout_api_key');
        $callbackUrl = config('services.heleket.payout_callback_url');

        if (!$merchantUuid || !$apiKey) {
            Log::error('Heleket payout config missing');
            return new PartnerPayoutResult(false, null, 'Heleket payout not configured');
        }

        $amount = (string) $payout->commission_amount;
        $currency = strtoupper($payout->currency);
        $network = $this->mapNetwork($payout->network);
        $address = $payout->wallet_address;

        $orderId = $payout instanceof AggregatedPayoutRequest
            ? 'partner-batch-' . $payout->batchIdentifier
            : 'partner-payout-' . $payout->id;

        $payload = [
            'amount' => $amount,
            'currency' => $currency,
            'order_id' => $orderId,
            'address' => $address,
            'is_subtract' => true,
        ];

        if ($network) {
            $payload['network'] = $network;
        }

        if ($callbackUrl) {
            $payload['url_callback'] = $callbackUrl;
        }

        try {

            $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
            $sign = md5(base64_encode($json) . $apiKey);

            $response = Http::withHeaders([
                'merchant' => $merchantUuid,
                'sign' => $sign,
                'Content-Type' => 'application/json',
            ])->withBody($json, 'application/json')
                ->post($baseUrl . '/v1/payout');

            $data = $response->json();

            if (!$response->successful()) {
                Log::error('Heleket payout HTTP error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return new PartnerPayoutResult(
                    false,
                    null,
                    $data['message'] ?? 'Heleket payout failed'
                );
            }

            if (($data['state'] ?? 1) !== 0) {
                Log::error('Heleket payout rejected', [
                    'response' => $data
                ]);

                return new PartnerPayoutResult(
                    false,
                    null,
                    $data['message'] ?? 'Heleket payout rejected'
                );
            }

            $result = $data['result'];

            return new PartnerPayoutResult(
                true,
                $result['uuid'] ?? $result['txid'] ?? null,
                null,
                [
                    'uuid' => $result['uuid'] ?? null,
                    'txid' => $result['txid'] ?? null,
                    'status' => $result['status'] ?? null,
                    'commission' => $result['commission'] ?? null,
                    'merchant_amount' => $result['merchant_amount'] ?? null,
                ]
            );

        } catch (Throwable $e) {

            Log::error('Heleket payout exception', [
                'error' => $e->getMessage(),
            ]);

            return new PartnerPayoutResult(false, null, $e->getMessage());
        }
    }

    private function mapNetwork(?string $network): ?string
    {
        if (!$network) {
            return null;
        }

        return match (strtoupper($network)) {
            'TRC20', 'TRON' => 'TRON',
            'ERC20', 'ETH', 'ETHEREUM' => 'ETH',
            'BEP20', 'BSC' => 'BSC',
            'POLYGON', 'MATIC' => 'POLYGON',
            default => strtoupper($network),
        };
    }
}
