<?php

namespace App\Http\Controllers;

use App\Models\ShortlinkLink;
use App\Models\ShortlinkTransaction;
use App\Models\User;
use App\Services\PartnerCommissionService;
use App\Services\ShortenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HeleketWebhookController extends Controller
{
    /**
     * Handle Heleket payment webhook. Single source of truth for payment completion.
     * - Verifies signature
     * - Marks transaction paid
     * - For balance top-up: credits user
     * - For link payment: generates and stores links
     */
    public function handle(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $body = json_decode($rawBody, true);

        if (! is_array($body)) {
            Log::warning('Heleket webhook: invalid JSON body');
            return response()->json(['error' => 'Invalid body'], 400);
        }

        $orderId = $body['order_id'] ?? null;
        $status = strtolower($body['status'] ?? $body['payment_status'] ?? '');

        if (! $orderId) {
            Log::warning('Heleket webhook: missing order_id');
            return response()->json(['error' => 'Missing order_id'], 400);
        }

        if (! $this->verifySignature($body, $rawBody)) {
            Log::warning('Heleket webhook: invalid signature', ['order_id' => $orderId]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        if (in_array($status, ['fail', 'cancel', 'expired', 'wrong_amount', 'system_fail'])) {
            ShortlinkTransaction::where('order_id', $orderId)->update(['status' => 'failed']);
            Log::info('Heleket webhook: order failed', ['order_id' => $orderId, 'status' => $status]);
            return response()->json(['ok' => true]);
        }

        if (! in_array($status, ['paid', 'paid_over'])) {
            return response()->json(['ok' => true]);
        }

        $tx = ShortlinkTransaction::where('order_id', $orderId)->lockForUpdate()->first();
        if (! $tx) {
            Log::warning('Heleket webhook: transaction not found', ['order_id' => $orderId]);
            return response()->json(['ok' => true]);
        }

        if ($tx->status === 'paid') {
            Log::info('Heleket webhook: duplicate webhook ignored', ['order_id' => $orderId]);
            return response()->json(['ok' => true]);
        }

        $providerRef = $body['uuid'] ?? $body['txid'] ?? $tx->provider_ref;
        $isBalanceTopup = $tx->isBalanceTopup();
        $isShortlinkPayment = $tx->isShortlinkPayment();

        DB::transaction(function () use ($tx, $providerRef, $isBalanceTopup, $isShortlinkPayment) {
            $tx->update([
                'status' => 'paid',
                'provider_ref' => $providerRef,
            ]);

            if ($isBalanceTopup) {
                $userId = (int) substr($tx->identifier, 5);
                User::where('id', $userId)->increment('balance', (float) $tx->amount);
                $this->recordPartnerCommission($userId, (float) $tx->amount, 'heleket_topup', $tx->order_id);
                Log::info('Heleket webhook: balance credited', ['order_id' => $tx->order_id, 'user_id' => $userId]);
            } elseif ($isShortlinkPayment) {
                $links = app(ShortenService::class)->shorten($tx->url, $tx->count);
                $tx->update(['result_links' => $links]);
                $this->storeShortlinkLinks($tx, $links);
                $userId = (int) substr($tx->identifier, 5);
                $this->recordPartnerCommission($userId, (float) $tx->amount, 'heleket_shortlink', $tx->order_id);
                Log::info('Heleket webhook: links generated', ['order_id' => $tx->order_id, 'count' => count($links)]);
            }
        });

        return response()->json(['ok' => true]);
    }

    private function verifySignature(array $body, string $rawBody): bool
    {
        $sign = $body['sign'] ?? null;
        if (! $sign) {
            return false;
        }

        $paymentKey = config('services.heleket.payment_key');
        if (! $paymentKey) {
            Log::warning('Heleket webhook: payment_key not configured');
            return false;
        }

        $data = $body;
        unset($data['sign']);
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $hash = md5(base64_encode($json) . $paymentKey);

        return hash_equals($hash, $sign);
    }

    private function storeShortlinkLinks(ShortlinkTransaction $tx, array $links): void
    {
        if (! str_starts_with($tx->identifier ?? '', 'user:')) {
            return;
        }
        $userId = (int) substr($tx->identifier, 5);
        $user = User::find($userId);
        if (! $user) {
            return;
        }
        $sub = $user->activeSubscription();
        $batchId = 'batch-' . uniqid();
        $expiresAt = $sub ? null : now()->addDays(30);
        foreach ($links as $i => $shortUrl) {
            ShortlinkLink::create([
                'user_id' => $userId,
                'user_subscription_id' => $sub?->id,
                'original_url' => $tx->url,
                'short_url' => $shortUrl,
                'batch_index' => $i + 1,
                'batch_id' => $batchId,
                'expires_at' => $expiresAt,
            ]);
        }
    }

    private function recordPartnerCommission(int $userId, float $amount, string $sourceType, string $sourceId): void
    {
        $user = User::find($userId);
        if (!$user) {
            return;
        }
        app(PartnerCommissionService::class)->recordCommission($user, $amount, $sourceType, $sourceId, 'heleket');
    }
}
