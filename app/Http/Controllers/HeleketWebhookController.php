<?php

namespace App\Http\Controllers;

use App\Models\ShortlinkTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HeleketWebhookController extends Controller
{
    /**
     * Handle Heleket payment webhook.
     * On paid status, update transaction and cache for paymentSuccess.
     */
    public function handle(Request $request)
    {
        $body = $request->all();
        $orderId = $body['order_id'] ?? null;
        $status = strtolower($body['payment_status'] ?? $body['status'] ?? '');

        if (!$orderId) {
            return response()->json(['error' => 'Missing order_id'], 400);
        }

        if (in_array($status, ['paid', 'paid_over'])) {
            Cache::put("shortlink_paid_{$orderId}", true, 3600);
            ShortlinkTransaction::where('order_id', $orderId)->update([
                'status' => 'paid',
                'provider_ref' => $body['uuid'] ?? $body['provider_ref'] ?? null,
            ]);
            Log::info('Heleket webhook: order paid', ['order_id' => $orderId]);
        } elseif (in_array($status, ['fail', 'cancel', 'expired'])) {
            ShortlinkTransaction::where('order_id', $orderId)->update(['status' => 'failed']);
        }

        return response()->json(['ok' => true]);
    }
}
