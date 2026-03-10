<?php

namespace App\Http\Controllers;

use App\Models\ShortlinkTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BalanceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $heleketAvailable = config('services.heleket.merchant') && config('services.heleket.payment_key');
        $prefillAmount = $request->query('amount');
        if ($prefillAmount !== null) {
            $prefillAmount = max(0.10, min(10000, (float) $prefillAmount));
        }
        $plans = \App\Models\SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();

        return view('balance.index', [
            'balance' => $user->balance,
            'transactions' => ShortlinkTransaction::where('identifier', 'user:' . $user->id)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(),
            'heleketAvailable' => $heleketAvailable,
            'prefillAmount' => $prefillAmount ?? 10,
            'plans' => $plans,
            'activeSubscription' => $user->activeSubscription(),
            'lastExpiredSubscription' => $user->lastExpiredSubscription(),
        ]);
    }

    public function initiateHeleketTopup(Request $request): RedirectResponse
    {
        $validated = $request->validate(['amount' => 'required|numeric|min:0.1|max:10000']);
        $amount = (float) $validated['amount'];
        if ($amount < 0.10 || $amount > 10000) {
            return redirect()->route('balance.index')->with('error', 'Amount must be between $0.10 and $10,000.');
        }

        $merchant = config('services.heleket.merchant');
        $paymentKey = config('services.heleket.payment_key');
        if (!$merchant || !$paymentKey) {
            return redirect()->route('balance.index')->with('error', 'Heleket is not configured.');
        }

        $user = Auth::user();
        $orderId = 'bal-' . uniqid();

        ShortlinkTransaction::create([
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => 'USD',
            'status' => 'pending',
            'identifier' => 'user:' . $user->id,
            'count' => 0,
            'url' => null,
            'provider_ref' => 'heleket_topup',
        ]);

        $baseUrl = config('services.heleket.base', 'https://api.heleket.com');
        $urlSuccess = route('balance.heleket.success') . '?order_id=' . $orderId;
        $urlReturn = route('balance.index');
        $webhookUrl = url('/api/webhooks/payments/heleket');

        $payload = [
            'amount' => $amount,
            'currency' => 'USD',
            'order_id' => $orderId,
            'url_success' => $urlSuccess,
            'url_return' => $urlReturn,
            'webhook_url' => $webhookUrl,
        ];

        $jsonBody = json_encode($payload);
        $encoded = base64_encode($jsonBody);
        $sign = md5($encoded . $paymentKey);

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'merchant' => $merchant,
            'sign' => $sign,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->withBody($jsonBody, 'application/json')->post(rtrim($baseUrl, '/') . '/v1/payment', $payload);

        $data = $response->json();
        if (($data['state'] ?? -1) !== 0) {
            return redirect()->route('balance.index')->with('error', $data['message'] ?? 'Payment initiation failed');
        }

        $payUrl = $data['result']['url'] ?? null;
        if (!$payUrl) {
            return redirect()->route('balance.index')->with('error', 'Invalid payment response');
        }

        return redirect()->away($payUrl);
    }

    /**
     * UI-only. Never mutates payment state. Webhook credits balance.
     */
    public function heleketTopupSuccess(Request $request): RedirectResponse
    {
        return $this->topupSuccessRedirect($request);
    }

    /**
     * UI-only. Never mutates payment state. Webhook credits balance.
     */
    public function tronTopupSuccess(Request $request): RedirectResponse
    {
        return $this->topupSuccessRedirect($request);
    }

    private function topupSuccessRedirect(Request $request): RedirectResponse
    {
        $orderId = $request->query('order_id');
        $user = Auth::user();
        if (! $user || ! $orderId) {
            return redirect()->route('balance.index')->with('error', 'Invalid request.');
        }

        $tx = ShortlinkTransaction::where('order_id', $orderId)
            ->where('identifier', 'user:' . $user->id)
            ->first();

        if (! $tx) {
            return redirect()->route('balance.index')->with('error', 'Transaction not found.');
        }

        if ($tx->status === 'failed') {
            return redirect()->route('balance.index')->with('error', 'Payment failed.');
        }

        if ($tx->status !== 'paid') {
            return redirect()->route('balance.index')
                ->with('info', 'Payment received. Your balance will be updated shortly. Please refresh the page.');
        }

        return redirect()->route('balance.index')
            ->with('success', 'Balance topped up: $' . number_format($tx->amount, 2));
    }

    public function prepareTopup(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate(['amount' => 'required|numeric|min:0.1|max:10000']);
        $amount = (float) $data['amount'];
        if ($amount < 0.10) {
            return response()->json(['error' => 'Minimum top-up is $0.10'], 400);
        }
        if ($amount > 10000) {
            return response()->json(['error' => 'Maximum top-up is $10,000'], 400);
        }

        $orderId = 'bal-' . uniqid();
        $user = Auth::user();

        ShortlinkTransaction::create([
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => 'USD',
            'status' => 'pending',
            'identifier' => 'user:' . $user->id,
            'count' => 0,
            'url' => null,
            'provider_ref' => 'tron_topup',
        ]);

        $request->session()->put('balance_topup_order', $orderId);

        return response()->json(['order_id' => $orderId, 'amount' => $amount]);
    }

    /**
     * Dev-only: add balance for testing. Only available when APP_ENV=local.
     */
    public function testAddBalance(Request $request): RedirectResponse
    {
        if (! in_array(app()->environment(), ['local', 'testing'])) {
            abort(404);
        }

        $amount = min(max((float) $request->query('amount', 10), 0.01), 10000);
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('auth.login')->with('error', 'You must be logged in.');
        }

        $user->increment('balance', $amount);

        return redirect()->route('balance.index')
            ->with('success', 'Balance added (test): $' . number_format($amount, 2));
    }
}
