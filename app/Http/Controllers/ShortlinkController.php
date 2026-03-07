<?php

namespace App\Http\Controllers;

use App\Models\ShortlinkSetting;
use App\Models\ShortlinkTransaction;
use App\Services\ShortenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShortlinkController extends Controller
{
    public const FREE_TRIAL_LIMIT = 50;

    public function __construct(
        private ShortenService $shortenService
    ) {}

    public function index(Request $request)
    {
        $freeTrialUsed = DB::table('shortlink_free_trial_uses')
            ->where('ip_address', $request->ip())
            ->exists();
        $remaining = $freeTrialUsed ? 0 : self::FREE_TRIAL_LIMIT;

        return view('shortlink.index', ['remaining' => $remaining]);
    }

    private function getIdentifier(Request $request): string
    {
        $fingerprint = $request->input('fingerprint');
        if ($fingerprint && strlen($fingerprint) <= 128) {
            return $fingerprint;
        }
        return 'ip:' . $request->ip();
    }

    private function hasUsedFreeTrial(string $identifier, string $ip): bool
    {
        return DB::table('shortlink_free_trial_uses')
            ->where('identifier', $identifier)
            ->orWhere('ip_address', $ip)
            ->exists();
    }

    private function recordFreeTrialUse(string $identifier, string $ip): void
    {
        DB::table('shortlink_free_trial_uses')->insert([
            'identifier' => $identifier,
            'ip_address' => $ip,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function generate(Request $request): \Illuminate\Http\JsonResponse|StreamedResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url'],
            'count' => ['required', 'integer', 'min:1'],
            'fingerprint' => ['nullable', 'string', 'max:128'],
        ]);

        $url = $validated['url'];
        $count = (int) $validated['count'];
        $identifier = $this->getIdentifier($request);
        $ip = $request->ip();

        $freeTrialUsed = $this->hasUsedFreeTrial($identifier, $ip);
        $withinFreeLimit = $count <= self::FREE_TRIAL_LIMIT;

        if ($freeTrialUsed || !$withinFreeLimit) {
            $request->session()->put('shortlink_pending', [
                'url' => $url,
                'count' => $count,
                'free_trial_exhausted' => $freeTrialUsed,
                'identifier' => $identifier,
            ]);
            return response()->json([
                'redirect' => route('shortlink.payment'),
                'requires_payment' => true,
            ]);
        }

        $links = $this->shortenService->shorten($url, $count);
        $this->recordFreeTrialUse($identifier, $ip);
        $request->session()->put('shortlink_result', $links);

        return response()->json([
            'success' => true,
            'count' => count($links),
            'links' => $links,
            'download_url' => route('shortlink.download'),
            'remaining' => 0,
        ]);
    }

    public function download(Request $request): StreamedResponse
    {
        $links = $request->session()->pull('shortlink_result', []);
        if (empty($links)) {
            abort(404, 'No links to download. Please generate links first.');
        }

        $filename = 'shortlinks_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($links) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['#', 'Shortened URL']);
            foreach ($links as $i => $link) {
                fputcsv($handle, [$i + 1, $link]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function payment(Request $request)
    {
        $pending = $request->session()->get('shortlink_pending');
        if (!$pending) {
            return redirect()->route('shortlink.index')
                ->with('error', 'No pending generation. Please fill the form again.');
        }

        $count = (int) $pending['count'];
        $freeTrialExhausted = (bool) ($pending['free_trial_exhausted'] ?? false);

        $pricePerLink = (float) ShortlinkSetting::get('price_per_link', '0.01');
        $minAmount = (float) ShortlinkSetting::get('min_amount', '0.10');

        if ($freeTrialExhausted) {
            $amount = max($minAmount, round($count * $pricePerLink, 2));
            $reason = 'free_trial_used';
        } else {
            $overLimit = $count - self::FREE_TRIAL_LIMIT;
            $amount = max($minAmount, round($overLimit * $pricePerLink, 2));
            $reason = 'over_limit';
        }

        return view('shortlink.payment', [
            'url' => $pending['url'],
            'count' => $count,
            'amount' => $amount,
            'freeLimit' => self::FREE_TRIAL_LIMIT,
            'freeTrialExhausted' => $freeTrialExhausted,
            'reason' => $reason,
            'coinrushStoreKey' => config('services.coinrush.store_key'),
            'coinrushApiUrl' => config('services.coinrush.api_url', 'https://coinrush.link/store'),
        ]);
    }

    public function initiatePayment(Request $request)
    {
        $pending = $request->session()->get('shortlink_pending');
        if (!$pending) {
            return redirect()->route('shortlink.index')
                ->with('error', 'Session expired. Please try again.');
        }

        $count = (int) $pending['count'];
        $freeTrialExhausted = (bool) ($pending['free_trial_exhausted'] ?? false);
        $pricePerLink = (float) ShortlinkSetting::get('price_per_link', '0.01');
        $minAmount = (float) ShortlinkSetting::get('min_amount', '0.10');
        $amount = $freeTrialExhausted
            ? max($minAmount, round($count * $pricePerLink, 2))
            : max($minAmount, round(($count - self::FREE_TRIAL_LIMIT) * $pricePerLink, 2));

        $merchant = config('services.heleket.merchant');
        $paymentKey = config('services.heleket.payment_key');

        if (!$merchant || !$paymentKey) {
            return back()->with('error', 'Payment gateway (Heleket) is not configured. Set HELEKET_MERCHANT and HELEKET_PAYMENT_KEY in .env');
        }

        $baseUrl = config('services.heleket.base', 'https://api.heleket.com');
        $orderId = 'sl-' . uniqid();
        $urlSuccess = route('shortlink.payment-success');
        $urlReturn = route('shortlink.payment');
        $webhookUrl = url('/api/webhooks/payments/heleket');

        $payload = [
            'amount' => $amount,
            'currency' => 'USD',
            'order_id' => $orderId,
            'url_success' => $urlSuccess . '?order_id=' . $orderId,
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
            return back()->with('error', $data['message'] ?? 'Payment initiation failed');
        }

        $payUrl = $data['result']['url'] ?? null;
        if (!$payUrl) {
            return back()->with('error', 'Invalid payment response');
        }

        $request->session()->put('shortlink_order_id', $orderId);

        ShortlinkTransaction::create([
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => 'USD',
            'status' => 'pending',
            'identifier' => $pending['identifier'] ?? null,
            'count' => $count,
            'url' => $pending['url'] ?? null,
        ]);

        return redirect()->away($payUrl);
    }

    public function paymentSuccess(Request $request)
    {
        $orderId = $request->query('order_id');
        $pending = $request->session()->get('shortlink_pending');

        if (!$pending || !$orderId) {
            return redirect()->route('shortlink.index')
                ->with('error', 'Invalid session. Please try again.');
        }

        // In production: verify payment via webhook/DB before generating
        // For now we trust the redirect (user came from Heleket success URL)
        $links = $this->shortenService->shorten($pending['url'], $pending['count']);
        $request->session()->forget(['shortlink_pending', 'shortlink_order_id']);
        $request->session()->put('shortlink_result', $links);

        return redirect()->route('shortlink.index')
            ->with('success', count($links) . ' links generated! Download your file below.')
            ->with('download_ready', true);
    }

    public function prepareTronPayment(Request $request): \Illuminate\Http\JsonResponse
    {
        $pending = $request->session()->get('shortlink_pending');
        if (!$pending) {
            return response()->json(['error' => 'Session expired'], 400);
        }

        $count = (int) $pending['count'];
        $freeTrialExhausted = (bool) ($pending['free_trial_exhausted'] ?? false);
        $pricePerLink = (float) ShortlinkSetting::get('price_per_link', '0.01');
        $minAmount = (float) ShortlinkSetting::get('min_amount', '0.10');
        $amount = $freeTrialExhausted
            ? max($minAmount, round($count * $pricePerLink, 2))
            : max($minAmount, round(($count - self::FREE_TRIAL_LIMIT) * $pricePerLink, 2));

        $orderId = 'sl-' . uniqid();

        ShortlinkTransaction::create([
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => 'USD',
            'status' => 'pending',
            'identifier' => $pending['identifier'] ?? null,
            'count' => $count,
            'url' => $pending['url'] ?? null,
            'provider_ref' => 'tron',
        ]);

        $request->session()->put('shortlink_order_id', $orderId);

        return response()->json(['order_id' => $orderId, 'amount' => $amount]);
    }

    public function paymentTronSuccess(Request $request)
    {
        $orderId = $request->query('order_id');
        $pending = $request->session()->get('shortlink_pending');

        if (!$pending || !$orderId) {
            return redirect()->route('shortlink.index')
                ->with('error', 'Invalid session. Please try again.');
        }

        $transaction = ShortlinkTransaction::where('order_id', $orderId)->first();

        // Idempotent: if already processed (paid + links in session), redirect with download
        if ($transaction?->status === 'paid') {
            $links = $request->session()->get('shortlink_result', []);
            if (!empty($links)) {
                return redirect()->route('shortlink.index')
                    ->with('success', count($links) . ' links generated! Download your file below.')
                    ->with('download_ready', true);
            }
        }

        // Mark as paid if still pending (webhook may have already done this)
        if ($transaction && $transaction->status === 'pending') {
            $txRef = $request->query('transaction_id');
            $transaction->update([
                'status' => 'paid',
                'provider_ref' => 'tron' . ($txRef ? ':' . $txRef : ''),
            ]);
        }

        $links = $this->shortenService->shorten($pending['url'], $pending['count']);
        $request->session()->forget(['shortlink_pending', 'shortlink_order_id']);
        $request->session()->put('shortlink_result', $links);

        return redirect()->route('shortlink.index')
            ->with('success', count($links) . ' links generated! Download your file below.')
            ->with('download_ready', true);
    }
}
