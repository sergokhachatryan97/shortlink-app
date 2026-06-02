<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\DefersPartnerCommissionAfterPayment;
use App\Models\ExternalClient;
use App\Models\ShortlinkLink;
use App\Models\ShortlinkTransaction;
use App\Models\User;
use App\Services\BalanceService;
use App\Services\ShortenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class YooKassaWebhookController extends Controller
{
    use DefersPartnerCommissionAfterPayment;

    public function handle(Request $request): JsonResponse
    {
        $body = $request->all();
        $event = $body['event'] ?? '';
        $object = $body['object'] ?? [];

        $paymentId = $object['id'] ?? null;
        $orderId = $object['metadata']['order_id'] ?? null;
        $status = $object['status'] ?? '';

        if (! $orderId) {
            Log::warning('YooKassa webhook: missing order_id in metadata', ['payment_id' => $paymentId]);

            return response()->json(['ok' => true]);
        }

        if ($event === 'payment.canceled' || $status === 'canceled') {
            ShortlinkTransaction::where('order_id', $orderId)->update(['status' => 'failed']);
            Log::info('YooKassa webhook: payment canceled', ['order_id' => $orderId]);

            return response()->json(['ok' => true]);
        }

        if ($event !== 'payment.succeeded' && $status !== 'succeeded') {
            return response()->json(['ok' => true]);
        }

        $tx = ShortlinkTransaction::where('order_id', $orderId)->lockForUpdate()->first();
        if (! $tx) {
            Log::warning('YooKassa webhook: transaction not found', ['order_id' => $orderId]);

            return response()->json(['ok' => true]);
        }

        if ($tx->status === 'paid') {
            Log::info('YooKassa webhook: duplicate webhook ignored', ['order_id' => $orderId]);

            return response()->json(['ok' => true]);
        }

        $providerRef = 'yookassa:'.$paymentId;
        $isBalanceTopup = $tx->isBalanceTopup();
        $isShortlinkPayment = $tx->isShortlinkPayment();

        DB::transaction(function () use ($tx, $providerRef, $isBalanceTopup, $isShortlinkPayment) {
            $tx->update([
                'status' => 'paid',
                'provider_ref' => $providerRef,
            ]);

            if ($isBalanceTopup) {
                $userId = (int) substr($tx->identifier, 5);
                app(BalanceService::class)->incrementBalance(User::class, $userId, $tx->amount);
                ExternalClient::syncBalanceFromUserWallet($userId);
                $this->deferPartnerCommission($userId, (float) $tx->amount, 'yookassa_topup', $tx->order_id, 'yookassa');
                Log::info('YooKassa webhook: balance credited', ['order_id' => $tx->order_id, 'user_id' => $userId]);
            } elseif ($isShortlinkPayment) {
                $links = app(ShortenService::class)->shorten($tx->url, $tx->count);
                $tx->update(['result_links' => $links]);
                $this->storeShortlinkLinks($tx, $links);
                $userId = (int) substr($tx->identifier, 5);
                $this->deferPartnerCommission($userId, (float) $tx->amount, 'yookassa_shortlink', $tx->order_id, 'yookassa');
                Log::info('YooKassa webhook: links generated', ['order_id' => $tx->order_id, 'count' => count($links)]);
            } else {
                Log::warning('YooKassa webhook: paid transaction matched no handler', [
                    'order_id' => $tx->order_id,
                    'payment_kind' => $tx->payment_kind,
                ]);
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
        $batchId = 'batch-'.uniqid();
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
}
