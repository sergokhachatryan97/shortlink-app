<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientBalanceException;
use App\Models\ShortlinkLink;
use App\Models\ShortlinkSetting;
use App\Models\ShortlinkTransaction;
use App\Models\SiteStat;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\BalanceService;
use App\Services\ShortenService;
use App\Services\ShortlinkEntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShortlinkController extends Controller
{
    public const FREE_TRIAL_LIMIT = ShortlinkEntitlementService::FREE_TRIAL_LIMIT;

    public function __construct(
        private ShortenService $shortenService,
        private ShortlinkEntitlementService $entitlement,
        private BalanceService $balanceService
    ) {}

    public function index(Request $request)
    {
        $identifier = $this->getIdentifier($request);
        $ip = $request->ip();
        $remaining = $this->entitlement->getRemainingFreeTrial($identifier, $ip);

        $links = [];
        if (session('download_ready')) {
            $links = $request->session()->get('shortlink_result', []);
        }

        $atPlanLimit = false;
        $atDailyPlanLimit = false;
        $planName = null;
        $planLimit = self::FREE_TRIAL_LIMIT;
        $planUsed = self::FREE_TRIAL_LIMIT - $remaining;
        $planRemaining = $remaining;
        $planDailyLimit = null;
        $planUsedToday = null;
        $planDailyRemaining = null;
        $pricePerLink = (float) ShortlinkSetting::get('price_per_link', '0.01');
        $user = $request->user();
        if ($user) {
            $sub = $user->activeSubscription();
            if ($sub) {
                $plan = $sub->plan;
                $planName = $plan->getTranslatedName();
                $planLimit = (int) $plan->links_limit;
                $planUsed = ShortlinkLink::where('user_subscription_id', $sub->id)->count();
                $planRemaining = $plan->isUnlimited() ? null : max(0, $planLimit - $planUsed);

                if ($plan->hasDailyLinksLimit()) {
                    $planDailyLimit = (int) $plan->daily_links_limit;
                    $planUsedToday = SubscriptionPlan::countSubscriptionLinksToday((int) $sub->id);
                    $planDailyRemaining = max(0, $planDailyLimit - $planUsedToday);
                    if ($planDailyRemaining <= 0) {
                        $atDailyPlanLimit = true;
                    }
                }

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
            'atDailyPlanLimit' => $atDailyPlanLimit,
            'pricePerLink' => $pricePerLink,
            'planName' => $planName,
            'planLimit' => $planLimit,
            'planUsed' => $planUsed,
            'planRemaining' => $planRemaining,
            'planDailyLimit' => $planDailyLimit,
            'planUsedToday' => $planUsedToday,
            'planDailyRemaining' => $planDailyRemaining,
            'plans' => $plans,
            'activeSubscription' => $activeSubscription,
            'balance' => $balance,
            'lifetimeLinksGenerated' => SiteStat::lifetimeLinksGenerated(),
        ]);
    }

    private function getIdentifier(Request $request): string
    {
        $user = $request->user();
        if ($user) {
            return 'user:'.$user->id;
        }
        $fingerprint = $request->input('fingerprint');
        if ($fingerprint && strlen($fingerprint) <= 128) {
            return $fingerprint;
        }

        return 'ip:'.$request->ip();
    }

    public function generate(Request $request): JsonResponse|StreamedResponse
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

        $user = $request->user();
        $sub = $user?->activeSubscription();
        if ($user && $sub) {
            return $this->generateForSubscribedUser($request, $user, $sub, $url, $count, $identifier, $ip);
        }

        $remaining = $this->entitlement->getRemainingFreeTrial($identifier, $ip);
        $freeTrialExhausted = $remaining <= 0;
        $withinFreeLimit = $count <= $remaining;
        $requiresPayment = $freeTrialExhausted || ! $withinFreeLimit;

        $amount = 0;
        if ($requiresPayment) {
            $pricePerLink = (float) ShortlinkSetting::get('price_per_link', '0.01');
            $amount = $freeTrialExhausted
                ? max($pricePerLink, round($count * $pricePerLink, 2))
                : max($pricePerLink, round(($count - $remaining) * $pricePerLink, 2));
        }

        if ($requiresPayment && $user) {
            try {
                $this->balanceService->decrementBalance(User::class, (int) $user->id, $amount);
                $links = $this->shortenService->shorten($url, $count);
                $batchId = 'batch-'.uniqid();
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
                ], $this->userStatusPayload($user), $this->lifetimeStatPayload()));
            } catch (InsufficientBalanceException) {
                // Fall through to payment redirect below.
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
        $this->entitlement->recordFreeTrialUse($identifier, $ip, $freeCount);
        $request->session()->put('shortlink_result', $links);

        $newRemaining = $remaining - $freeCount;

        return response()->json(array_merge([
            'success' => true,
            'count' => count($links),
            'links' => $links,
            'download_url' => route('shortlink.download'),
            'remaining' => $newRemaining,
        ], $this->userStatusPayload($user), $this->lifetimeStatPayload()));
    }

    /**
     * Active subscribers: free-trial allowance first, then plan-included links, then balance.
     */
    private function generateForSubscribedUser(
        Request $request,
        User $user,
        UserSubscription $sub,
        string $url,
        int $count,
        string $identifier,
        string $ip
    ): JsonResponse {
        $trialRemaining = $this->entitlement->getRemainingFreeTrial($identifier, $ip);
        $plan = $sub->plan;
        $currentCount = ShortlinkLink::where('user_subscription_id', $sub->id)->count();
        $pricePerLink = (float) ShortlinkSetting::get('price_per_link', '0.01');

        $fromTrial = min($count, $trialRemaining);
        $afterTrial = $count - $fromTrial;

        if ($plan->isUnlimited() && $plan->hasDailyLinksLimit()) {
            $usedToday = SubscriptionPlan::countSubscriptionLinksToday((int) $sub->id);
            $roomToday = max(0, (int) $plan->daily_links_limit - $usedToday);
            if ($afterTrial > $roomToday) {
                return response()->json(array_merge([
                    'success' => false,
                    'error' => 'daily_limit',
                    'message' => __('messages.shortlink.daily_limit_exceeded', ['remaining' => $roomToday]),
                    'daily_links_remaining' => $roomToday,
                ], $this->userStatusPayload($user)), 422);
            }
        }

        if ($plan->isUnlimited()) {
            $fromPlan = $afterTrial;
            $fromBalance = 0;
        } else {
            $slots = max(0, (int) $plan->links_limit - $currentCount);
            $fromPlan = min($afterTrial, $slots);
            $fromBalance = $afterTrial - $fromPlan;
        }

        $planAmount = $fromBalance > 0
            ? max($pricePerLink, round($fromBalance * $pricePerLink, 2))
            : 0;

        if ($planAmount > 0) {
            try {
                $this->balanceService->decrementBalance(User::class, (int) $user->id, $planAmount);
            } catch (InsufficientBalanceException) {
                $request->session()->put('shortlink_pending', [
                    'url' => $url,
                    'count' => $fromBalance,
                    'free_trial_exhausted' => true,
                    'identifier' => 'user:'.$user->id,
                ]);

                return response()->json([
                    'redirect' => route('shortlink.payment'),
                    'requires_payment' => true,
                ]);
            }
        }

        $links = $this->shortenService->shorten($url, $count);
        $batchId = 'batch-'.uniqid();
        $idx = 0;

        foreach (array_slice($links, 0, $fromTrial) as $link) {
            ShortlinkLink::create([
                'user_id' => $user->id,
                'user_subscription_id' => null,
                'original_url' => $url,
                'short_url' => $link,
                'batch_index' => ++$idx,
                'batch_id' => $batchId,
                'expires_at' => now()->addDays(30),
                'from_free_trial_quota' => true,
            ]);
        }

        foreach (array_slice($links, $fromTrial, $fromPlan) as $link) {
            ShortlinkLink::create([
                'user_id' => $user->id,
                'user_subscription_id' => $sub->id,
                'original_url' => $url,
                'short_url' => $link,
                'batch_index' => ++$idx,
                'batch_id' => $batchId,
            ]);
        }

        foreach (array_slice($links, $fromTrial + $fromPlan) as $link) {
            ShortlinkLink::create([
                'user_id' => $user->id,
                'user_subscription_id' => $sub->id,
                'original_url' => $url,
                'short_url' => $link,
                'batch_index' => ++$idx,
                'batch_id' => $batchId,
            ]);
        }

        if ($fromTrial > 0) {
            $this->entitlement->recordFreeTrialUse($identifier, $ip, $fromTrial);
        }

        $request->session()->put('shortlink_result', $links);

        return response()->json(array_merge([
            'success' => true,
            'count' => count($links),
            'links' => $links,
            'download_url' => route('shortlink.download'),
            'remaining' => 0,
        ], $this->userStatusPayload($user), $this->lifetimeStatPayload()));
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
            $payload['plan_name'] = $plan->getTranslatedName();
            $payload['plan_limit'] = (int) $plan->links_limit;
            $payload['plan_used'] = $currentCount;
            $payload['plan_remaining'] = $plan->isUnlimited() ? null : max(0, (int) $plan->links_limit - $currentCount);
            if ($plan->hasDailyLinksLimit()) {
                $usedToday = SubscriptionPlan::countSubscriptionLinksToday((int) $sub->id);
                $payload['plan_daily_limit'] = (int) $plan->daily_links_limit;
                $payload['plan_used_today'] = $usedToday;
                $payload['plan_daily_remaining'] = max(0, (int) $plan->daily_links_limit - $usedToday);
            }
        } else {
            $payload['plan_name'] = null;
            $payload['plan_limit'] = self::FREE_TRIAL_LIMIT;
            $payload['plan_used'] = self::FREE_TRIAL_LIMIT - $this->entitlement->getRemainingFreeTrial('user:'.$user->id, request()->ip());
            $payload['plan_remaining'] = $this->entitlement->getRemainingFreeTrial('user:'.$user->id, request()->ip());
        }

        return $payload;
    }

    /** Current all-time links counter (for AJAX UI refresh). */
    private function lifetimeStatPayload(): array
    {
        return ['lifetime_links_generated' => SiteStat::lifetimeLinksGenerated()];
    }

    public function download(Request $request): StreamedResponse
    {
        $links = $request->session()->pull('shortlink_result', []);
        if (empty($links)) {
            abort(404, 'No links to download. Please generate links first.');
        }

        $filename = 'shortlinks_'.date('Y-m-d_His').'.csv';

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
        if (! $pending) {
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
        if (! $pending) {
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

        if (! $merchant || ! $paymentKey) {
            return back()->with('error', 'Payment gateway (Heleket) is not configured. Set HELEKET_MERCHANT and HELEKET_PAYMENT_KEY in .env');
        }

        $baseUrl = config('services.heleket.base', 'https://api.heleket.com');
        $orderId = 'sl-'.uniqid();
        $urlSuccess = route('shortlink.payment-success');
        $urlReturn = route('shortlink.payment');
        $webhookUrl = url('/api/webhooks/payments/heleket');

        $payload = [
            'amount' => $amount,
            'currency' => 'USD',
            'order_id' => $orderId,
            'url_success' => $urlSuccess.'?order_id='.$orderId,
            'url_return' => $urlReturn,
            'webhook_url' => $webhookUrl,
            'url_callback' => $webhookUrl,
        ];

        $jsonBody = json_encode($payload);
        $encoded = base64_encode($jsonBody);
        $sign = md5($encoded.$paymentKey);

        $response = Http::withHeaders([
            'merchant' => $merchant,
            'sign' => $sign,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->withBody($jsonBody, 'application/json')->post(rtrim($baseUrl, '/').'/v1/payment', $payload);

        $data = $response->json();
        if (($data['state'] ?? -1) !== 0) {
            return back()->with('error', $data['message'] ?? 'Payment initiation failed');
        }

        $payUrl = $data['result']['url'] ?? null;
        if (! $payUrl) {
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
            'payment_kind' => ShortlinkTransaction::KIND_SHORTLINK_PAYMENT,
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
            ->with('success', count($tx->result_links).' links generated! Download your file below.')
            ->with('download_ready', true)
            ->with('payment_provider', 'heleket');
    }

    public function prepareTronPayment(Request $request): JsonResponse
    {
        $pending = $request->session()->get('shortlink_pending');
        if (! $pending) {
            return response()->json(['error' => 'Session expired'], 400);
        }

        $count = (int) $pending['count'];
        $freeTrialExhausted = (bool) ($pending['free_trial_exhausted'] ?? false);
        $remaining = (int) ($pending['remaining'] ?? 0);
        $pricePerLink = (float) ShortlinkSetting::get('price_per_link', '0.01');
        $amount = $freeTrialExhausted
            ? max($pricePerLink, round($count * $pricePerLink, 2))
            : max($pricePerLink, round(max(0, $count - $remaining) * $pricePerLink, 2));

        $orderId = 'sl-'.uniqid();

        ShortlinkTransaction::create([
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => 'USD',
            'status' => 'pending',
            'identifier' => $pending['identifier'] ?? null,
            'count' => $count,
            'url' => $pending['url'] ?? null,
            'provider_ref' => 'tron',
            'payment_kind' => ShortlinkTransaction::KIND_SHORTLINK_PAYMENT,
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
            ->with('success', count($tx->result_links).' links generated! Download your file below.')
            ->with('download_ready', true)
            ->with('payment_provider', 'tron');
    }

    /**
     * Poll endpoint for payment-pending page. Returns JSON status.
     */
    public function paymentStatus(Request $request): JsonResponse
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
            $links[] = 'https://short.example/'.$i;
        }

        SiteStat::incrementLifetimeLinksGenerated(count($links));

        return redirect()->route('shortlink.index')
            ->with('success', $count.' links generated! Download your file below.')
            ->with('download_ready', true)
            ->with('payment_provider', 'tron')
            ->with('shortlink_result', $links);
    }
}
