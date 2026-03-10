<?php

namespace App\Http\Controllers;

use App\Models\ShortlinkLink;
use App\Models\ShortlinkSetting;
use App\Models\ShortlinkTransaction;
use App\Models\SubscriptionPlan;
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
        $planName = null;
        $planLimit = self::FREE_TRIAL_LIMIT;
        $planUsed = self::FREE_TRIAL_LIMIT - $remaining;
        $planRemaining = $remaining;
        $pricePerLink = (float) ShortlinkSetting::get('price_per_link', '0.01');
        $user = $request->user();
        if ($user) {
            $sub = $user->activeSubscription();
            if ($sub) {
                $plan = $sub->plan;
                $planName = $plan->name;
                $planLimit = (int) $plan->links_limit;
                $planUsed = ShortlinkLink::where('user_subscription_id', $sub->id)->count();
                $planRemaining = $plan->isUnlimited() ? null : max(0, $planLimit - $planUsed);

                if ($planLimit > 0 && $planUsed >= $planLimit) {
                    $atPlanLimit = true;
                }
            }
        }

        $plans = SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();
        $activeSubscription = $user?->activeSubscription();
        $balance = $user?->balance ?? 0;

        return view('shortlink.index', [
            'remaining' => $remaining,
            'links' => $links,
            'atPlanLimit' => $atPlanLimit,
            'pricePerLink' => $pricePerLink,
            'planName' => $planName,
            'planLimit' => $planLimit,
            'planUsed' => $planUsed,
            'planRemaining' => $planRemaining,
            'plans' => $plans,
            'activeSubscription' => $activeSubscription,
            'balance' => $balance,
        ]);
    }

    private function getIdentifier(Request $request): string
    {
        $user = $request->user();
        if ($user) {
            return 'user:' . $user->id;
        }
        $fingerprint = $request->input('fingerprint');
        if ($fingerprint && strlen($fingerprint) <= 128) {
            return $fingerprint;
        }
        return 'ip:' . $request->ip();
    }

    /** Get total links already used for free trial. */
    private function getFreeTrialUsedCount(string $identifier, string $ip): int
    {
        $query = DB::table('shortlink_free_trial_uses');

        if (str_starts_with($identifier, 'user:')) {
            $query->where('identifier', $identifier);
        } else {
            $query->where(function ($q) use ($identifier, $ip) {
                $q->where('identifier', $identifier)->orWhere('ip_address', $ip);
            });
        }

        return (int) $query->sum('links_count');
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
            'count' => ['required', 'integer', 'min:1', 'max:1000'],
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
            $amount = $freeTrialExhausted
                ? max($pricePerLink, round($count * $pricePerLink, 2))
                : max($pricePerLink, round(($count - $remaining) * $pricePerLink, 2));
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
                    ? max((float) ShortlinkSetting::get('price_per_link', '0.01'), round($paidCount * (float) ShortlinkSetting::get('price_per_link', '0.01'), 2))
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
                    return response()->json(array_merge([
                        'success' => true,
                        'count' => count($links),
                        'links' => $links,
                        'download_url' => route('shortlink.download'),
                        'remaining' => 0,
                    ], $this->userStatusPayload($user)));
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
                $batchId = 'batch-' . uniqid();
                foreach ($links as $i => $link) {
                    ShortlinkLink::create([
                        'user_id' => $user->id,
                        'user_subscription_id' => null,
                        'original_url' => $url,
                        'short_url' => $link,
                        'batch_index' => $i + 1,
                        'batch_id' => $batchId,
                        'expires_at' => now()->addDays(30),
                    ]);
                }
                $request->session()->put('shortlink_result', $links);
                return response()->json(array_merge([
                    'success' => true,
                    'count' => count($links),
                    'links' => $links,
                    'download_url' => route('shortlink.download'),
                    'remaining' => 0,
                ], $this->userStatusPayload($user)));
            }
        }

        if ($requiresPayment) {
            $request->session()->put('shortlink_pending', [
                'url' => $url,
                'count' => $count,
                'free_trial_exhausted' => $freeTrialExhausted,
                'remaining' => $remaining,
                'identifier' => $identifier,
            ]);
            return response()->json([
                'redirect' => route('shortlink.payment'),
                'requires_payment' => true,
            ]);
        }

        $freeCount = min($count, $remaining);
        $links = $this->shortenService->shorten($url, $freeCount);
        $this->recordFreeTrialUse($identifier, $ip, $freeCount);
        $request->session()->put('shortlink_result', $links);

        $newRemaining = $remaining - $freeCount;

        return response()->json(array_merge([
            'success' => true,
            'count' => count($links),
            'links' => $links,
            'download_url' => route('shortlink.download'),
            'remaining' => $newRemaining,
        ], $this->userStatusPayload($user)));
    }

    /** Build balance + plan payload for frontend (no reload). */
    private function userStatusPayload($user): array
    {
        if (! $user) {
            return [];
        }
        $payload = ['balance' => (float) $user->fresh()->balance];
        $sub = $user->activeSubscription();
        if ($sub) {
            $plan = $sub->plan;
            $currentCount = ShortlinkLink::where('user_subscription_id', $sub->id)->count();
            $payload['plan_name'] = $plan->name;
            $payload['plan_limit'] = (int) $plan->links_limit;
            $payload['plan_used'] = $currentCount;
            $payload['plan_remaining'] = $plan->isUnlimited() ? null : max(0, (int) $plan->links_limit - $currentCount);
        } else {
            $payload['plan_name'] = null;
            $payload['plan_limit'] = self::FREE_TRIAL_LIMIT;
            $payload['plan_used'] = self::FREE_TRIAL_LIMIT - $this->getRemainingFreeTrial('user:' . $user->id, request()->ip());
            $payload['plan_remaining'] = $this->getRemainingFreeTrial('user:' . $user->id, request()->ip());
        }
        return $payload;
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
        $remaining = (int) ($pending['remaining'] ?? 0);

        $pricePerLink = (float) ShortlinkSetting::get('price_per_link', '0.01');

        if ($freeTrialExhausted) {
            $amount = max($pricePerLink, round($count * $pricePerLink, 2));
            $reason = 'free_trial_used';
        } else {
            $paidCount = max(0, $count - $remaining);
            $amount = max($pricePerLink, round($paidCount * $pricePerLink, 2));
            $reason = 'over_limit';
        }

        $heleketAvailable = config('services.heleket.merchant') && config('services.heleket.payment_key');

        return view('shortlink.payment', [
            'url' => $pending['url'],
            'count' => $count,
            'amount' => $amount,
            'pricePerLink' => $pricePerLink,
            'freeLimit' => self::FREE_TRIAL_LIMIT,
            'remaining' => $remaining,
            'freeTrialExhausted' => $freeTrialExhausted,
            'reason' => $reason,
            'coinrushStoreKey' => config('services.coinrush.store_key'),
            'coinrushApiUrl' => config('services.coinrush.api_url', 'https://coinrush.link/store'),
            'heleketAvailable' => $heleketAvailable,
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
        $remaining = (int) ($pending['remaining'] ?? 0);
        $pricePerLink = (float) ShortlinkSetting::get('price_per_link', '0.01');
        $amount = $freeTrialExhausted
            ? max($pricePerLink, round($count * $pricePerLink, 2))
            : max($pricePerLink, round(max(0, $count - $remaining) * $pricePerLink, 2));

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
            'provider_ref' => 'heleket',
        ]);

        return redirect()->away($payUrl);
    }

    /**
     * UI-only. Never mutates payment state. Reads transaction status from DB (webhook is source of truth).
     */
    public function paymentSuccess(Request $request)
    {
        $orderId = $request->query('order_id');
        if (! $orderId) {
            return redirect()->route('shortlink.index')->with('error', 'Invalid request.');
        }

        $tx = ShortlinkTransaction::where('order_id', $orderId)->first();
        if (! $tx) {
            return redirect()->route('shortlink.index')->with('error', 'Transaction not found.');
        }
        if ($tx->status === 'failed') {
            return redirect()->route('shortlink.index')->with('error', 'Payment failed.');
        }
        if ($tx->status !== 'paid' || empty($tx->result_links)) {
            return view('shortlink.payment-pending', [
                'orderId' => $orderId,
                'pollUrl' => route('shortlink.payment-status', ['order_id' => $orderId]),
            ]);
        }

        $request->session()->put('shortlink_result', $tx->result_links);
        $request->session()->forget(['shortlink_pending', 'shortlink_order_id']);

        return redirect()->route('shortlink.index')
            ->with('success', count($tx->result_links) . ' links generated! Download your file below.')
            ->with('download_ready', true)
            ->with('payment_provider', 'heleket');
    }

    public function prepareTronPayment(Request $request): \Illuminate\Http\JsonResponse
    {
        $pending = $request->session()->get('shortlink_pending');
        if (!$pending) {
            return response()->json(['error' => 'Session expired'], 400);
        }

        $count = (int) $pending['count'];
        $freeTrialExhausted = (bool) ($pending['free_trial_exhausted'] ?? false);
        $remaining = (int) ($pending['remaining'] ?? 0);
        $pricePerLink = (float) ShortlinkSetting::get('price_per_link', '0.01');
        $amount = $freeTrialExhausted
            ? max($pricePerLink, round($count * $pricePerLink, 2))
            : max($pricePerLink, round(max(0, $count - $remaining) * $pricePerLink, 2));

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

    /**
     * UI-only. Never mutates payment state. Reads transaction status from DB (webhook is source of truth).
     */
    public function paymentTronSuccess(Request $request)
    {
        $orderId = $request->query('order_id');
        if (! $orderId) {
            return redirect()->route('shortlink.index')->with('error', 'Invalid request.');
        }

        $tx = ShortlinkTransaction::where('order_id', $orderId)->first();
        if (! $tx) {
            return redirect()->route('shortlink.index')->with('error', 'Transaction not found.');
        }
        if ($tx->status === 'failed') {
            return redirect()->route('shortlink.index')->with('error', 'Payment failed.');
        }
        if ($tx->status !== 'paid' || empty($tx->result_links)) {
            return view('shortlink.payment-pending', [
                'orderId' => $orderId,
                'pollUrl' => route('shortlink.payment-status', ['order_id' => $orderId]),
            ]);
        }

        $request->session()->put('shortlink_result', $tx->result_links);
        $request->session()->forget(['shortlink_pending', 'shortlink_order_id']);

        return redirect()->route('shortlink.index')
            ->with('success', count($tx->result_links) . ' links generated! Download your file below.')
            ->with('download_ready', true)
            ->with('payment_provider', 'tron');
    }

    /**
     * Poll endpoint for payment-pending page. Returns JSON status.
     */
    public function paymentStatus(Request $request): \Illuminate\Http\JsonResponse
    {
        $orderId = $request->query('order_id');
        if (! $orderId) {
            return response()->json(['error' => 'Missing order_id'], 400);
        }

        $tx = ShortlinkTransaction::where('order_id', $orderId)->first();
        if (! $tx) {
            return response()->json(['status' => 'not_found']);
        }
        if ($tx->status === 'failed') {
            return response()->json(['status' => 'failed']);
        }
        if ($tx->status === 'paid' && ! empty($tx->result_links)) {
            return response()->json(['status' => 'paid', 'links' => $tx->result_links]);
        }

        return response()->json(['status' => 'pending']);
    }

    /**
     * Dev-only: simulate successful payment to test the links UI in browser.
     * Only available when APP_ENV=local.
     */
    public function paymentTestSuccess(Request $request)
    {
        if (! app()->environment('local')) {
            abort(404);
        }

        $count = min(max((int) $request->query('count', 10), 1), 100);
        $links = [];
        for ($i = 1; $i <= $count; $i++) {
            $links[] = 'https://short.example/' . $i;
        }

        return redirect()->route('shortlink.index')
            ->with('success', $count . ' links generated! Download your file below.')
            ->with('download_ready', true)
            ->with('payment_provider', 'tron')
            ->with('shortlink_result', $links);
    }
}
