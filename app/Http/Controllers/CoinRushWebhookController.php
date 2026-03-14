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

class CoinRushWebhookController extends Controller
{
    /**
     * Handle CoinRush (Tron) payment webhook. Single source of truth for payment completion.
     * - Marks transaction paid
     * - For balance top-up: credits user
     * - For link payment: generates and stores links
     *
     * Note: CoinRush webhook signature verification not documented. Consider IP whitelist or
     * verifying transaction exists and matches expected state before mutating.
     */
    public function handle(Request $request): JsonResponse
    {
        $body = $request->all();
        $transactionId = $body['transaction_id'] ?? $body['order_id'] ?? null;
        $status = strtolower($body['status'] ?? $body['payment_status'] ?? '');

        if (! $transactionId) {
            Log::warning('CoinRush webhook: missing transaction_id');
            return response()->json(['error' => 'Missing transaction_id'], 400);
        }

        if (in_array($status, ['expired', 'failed', 'cancelled', 'canceled'])) {
            ShortlinkTransaction::where('order_id', $transactionId)->update(['status' => 'failed']);
            Log::info('CoinRush webhook: order failed', ['order_id' => $transactionId, 'status' => $status]);
            return response()->json(['ok' => true]);
        }

        if (! in_array($status, ['completed', 'paid', 'success'])) {
            return response()->json(['ok' => true]);
        }

        $tx = ShortlinkTransaction::where('order_id', $transactionId)->lockForUpdate()->first();
        if (! $tx) {
            Log::warning('CoinRush webhook: transaction not found', ['order_id' => $transactionId]);
            return response()->json(['ok' => true]);
        }

        if ($tx->status === 'paid') {
            Log::info('CoinRush webhook: duplicate webhook ignored', ['order_id' => $transactionId]);
            return response()->json(['ok' => true]);
        }

        $providerRef = 'tron:' . ($body['tx_hash'] ?? $body['provider_ref'] ?? 'webhook');
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
                $this->recordPartnerCommission($userId, (float) $tx->amount, 'coinrush_topup', $tx->order_id);
                Log::info('CoinRush webhook: balance credited', ['order_id' => $tx->order_id, 'user_id' => $userId]);
            } elseif ($isShortlinkPayment) {
                $links = app(ShortenService::class)->shorten($tx->url, $tx->count);
                $tx->update(['result_links' => $links]);
                $this->storeShortlinkLinks($tx, $links);
                $userId = (int) substr($tx->identifier, 5);
                $this->recordPartnerCommission($userId, (float) $tx->amount, 'coinrush_shortlink', $tx->order_id);
                Log::info('CoinRush webhook: links generated', ['order_id' => $tx->order_id, 'count' => count($links)]);
            }
        });

        return response()->json(['ok' => true]);
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
        app(PartnerCommissionService::class)->recordCommission($user, $amount, $sourceType, $sourceId, 'coinrush');
    }
}
