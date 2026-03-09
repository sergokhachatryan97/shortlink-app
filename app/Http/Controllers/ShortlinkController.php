<?php

namespace App\Http\Controllers;

use App\Models\ShortlinkLink;
use App\Models\ShortlinkSetting;
use App\Models\ShortlinkTransaction;
use App\Models\UserSubscription;
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
        $identifier = $this->getIdentifier($request);
        $ip = $request->ip();
        $remaining = $this->getRemainingFreeTrial($identifier, $ip);

        $links = [];
        if (session('download_ready')) {
            $links = $request->session()->get('shortlink_result', []);
        }

        $atPlanLimit = false;
        $pricePerLink = (float) ShortlinkSetting::get('price_per_link', '0.01');
        $user = $request->user();
        if ($user) {
            $sub = $user->activeSubscription();
            if ($sub) {
                $plan = $sub->plan;
                if (!$plan->isUnlimited()) {
                    $currentCount = ShortlinkLink::where('user_subscription_id', $sub->id)->count();
                    $atPlanLimit = $currentCount >= (int) $plan->links_limit;
                }
            }
        }

        return view('shortlink.index', [
            'remaining' => $remaining,
            'links' => $links,
            'atPlanLimit' => $atPlanLimit,
            'pricePerLink' => $pricePerLink,
        ]);
    }

    private function getIdentifier(Request $request): string
    {
        $fingerprint = $request->input('fingerprint');
        if ($fingerprint && strlen($fingerprint) <= 128) {
            return $fingerprint;
        }
        return 'ip:' . $request->ip();
    }

    /** Get total links already used for free trial (identifier or IP). */
    private function getFreeTrialUsedCount(string $identifier, string $ip): int
    {
        return (int) DB::table('shortlink_free_trial_uses')
            ->where(function ($q) use ($identifier, $ip) {
                $q->where('identifier', $identifier)->orWhere('ip_address', $ip);
            })
            ->sum('links_count');
    }

    /** Remaining free links (0–50) before payment is required. */
    private function getRemainingFreeTrial(string $identifier, string $ip): int
    {
        $used = $this->getFreeTrialUsedCount($identifier, $ip);
        return max(0, self::FREE_TRIAL_LIMIT - $used);
    }

    private function hasUsedFreeTrial(string $identifier, string $ip): bool
    {
        return $this->getFreeTrialUsedCount($identifier, $ip) >= self::FREE_TRIAL_LIMIT;
    }

    private function recordFreeTrialUse(string $identifier, string $ip, int $count): void
    {
        DB::table('shortlink_free_trial_uses')->insert([
            'identifier' => $identifier,
            'ip_address' => $ip,
            'links_count' => $count,
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

        $remaining = $this->getRemainingFreeTrial($identifier, $ip);
        $freeTrialExhausted = $remaining <= 0;
        $withinFreeLimit = $count <= $remaining;
        $requiresPayment = $freeTrialExhausted || !$withinFreeLimit;

        $user = $request->user();
        $amount = 0;
        if ($requiresPayment) {
            $pricePerLink = (float) ShortlinkSetting::get('price_per_link', '0.01');
            $minAmount = (float) ShortlinkSetting::get('min_amount', '0.10');
            $amount = $freeTrialExhausted
                ? max($minAmount, round($count * $pricePerLink, 2))
                : max($minAmount, round(($count - $remaining) * $pricePerLink, 2));
        }

        if ($requiresPayment && $user) {
            $sub = $user->activeSubscription();
            if ($sub) {
                $plan = $sub->plan;
                $currentCount = ShortlinkLink::where('user_subscription_id', $sub->id)->count();
                $freeInPlan = $plan->isUnlimited() ? $count : max(0, (int) $plan->links_limit - $currentCount);
                $effectiveCount = $plan->isUnlimited()
                    ? $count
                    : min($count, (int) $plan->links_limit - $currentCount);
                if ($effectiveCount <= 0) {
                    $paidCount = $count;
                    $effectiveCount = $count;
                    $freeInPlan = 0;
                } else {
                    $paidCount = $effectiveCount - min($effectiveCount, $freeInPlan);
                }
                $planAmount = $paidCount > 0
                    ? max((float) ShortlinkSetting::get('min_amount', '0.10'), round($paidCount * (float) ShortlinkSetting::get('price_per_link', '0.01'), 2))
                    : 0;

                if ($planAmount === 0 || $user->balance >= $planAmount) {
                    if ($planAmount > 0) {
                        $user->decrement('balance', $planAmount);
                    }
                    $links = $this->shortenService->shorten($url, $effectiveCount);
                    $batchId = 'batch-' . uniqid();
                    foreach ($links as $i => $link) {
                        ShortlinkLink::create([
                            'user_id' => $user->id,
                            'user_subscription_id' => $sub->id,
                            'original_url' => $url,
                            'short_url' => $link,
                            'batch_index' => $i + 1,
                            'batch_id' => $batchId,
                        ]);
                    }
                    $request->session()->put('shortlink_result', $links);
                    return response()->json([
                        'success' => true,
                        'count' => count($links),
                        'links' => $links,
                        'download_url' => route('shortlink.download'),
                        'remaining' => 0,
                    ]);
                }
                $request->session()->put('shortlink_pending', [
                    'url' => $url,
                    'count' => $effectiveCount,
                    'free_trial_exhausted' => true,
                    'identifier' => 'user:' . $user->id,
                ]);
                return response()->json([
                    'redirect' => route('shortlink.payment'),
                    'requires_payment' => true,
                ]);
            }
            if ($user->balance >= $amount) {
                $user->decrement('balance', $amount);
                $links = $this->shortenService->shorten($url, $count);
                $request->session()->put('shortlink_result', $links);
                return response()->json([
                    'success' => true,
                    'count' => count($links),
                    'links' => $links,
                    'download_url' => route('shortlink.download'),
                    'remaining' => 0,
                ]);
            }
        }

        if ($requiresPayment) {
            $request->session()->put('shortlink_pending', [
                'url' => $url,
                'count' => $count,
                'free_trial_exhausted' => $freeTrialExhausted,
                'identifier' => $identifier,
            ]);
            return response()->json([
                'redirect' => route('shortlink.payment'),
                'requires_payment' => true,
            ]);
        }

        $links = $this->shortenService->shorten($url, $count);
        $this->recordFreeTrialUse($identifier, $ip, $count);
        $request->session()->put('shortlink_result', $links);

        $newRemaining = $remaining - $count;

        return response()->json([
            'success' => true,
            'count' => count($links),
            'links' => $links,
            'download_url' => route('shortlink.download'),
            'remaining' => $newRemaining,
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
