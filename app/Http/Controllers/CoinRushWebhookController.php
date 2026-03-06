<?php

namespace App\Http\Controllers;

use App\Models\ShortlinkTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CoinRushWebhookController extends Controller
{
    /**
     * Handle CoinRush (Tron) payment webhook.
     * Use this URL in your CoinRush store settings as the postback/webhook URL.
     */
    public function handle(Request $request)
    {
        $body = $request->all();
        $transactionId = $body['transaction_id'] ?? $body['order_id'] ?? null;
        $status = strtolower($body['status'] ?? $body['payment_status'] ?? '');

        if (!$transactionId) {
            return response()->json(['error' => 'Missing transaction_id'], 400);
        }

        if (in_array($status, ['completed', 'paid', 'success'])) {
            Cache::put("shortlink_tron_paid_{$transactionId}", true, 3600);
            ShortlinkTransaction::where('order_id', $transactionId)->update([
                'status' => 'paid',
                'provider_ref' => 'tron:' . ($body['tx_hash'] ?? $body['provider_ref'] ?? 'webhook'),
            ]);
            Log::info('CoinRush webhook: payment completed', ['transaction_id' => $transactionId]);
        } elseif (in_array($status, ['expired', 'failed', 'cancelled', 'canceled'])) {
            ShortlinkTransaction::where('order_id', $transactionId)->update(['status' => 'failed']);
        }

        return response()->json(['ok' => true]);
    }
}
