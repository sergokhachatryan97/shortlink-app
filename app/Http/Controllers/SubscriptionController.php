<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientBalanceException;
use App\Models\PromoCodeUsage;
use App\Models\ShortlinkTransaction;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\BalanceService;
use App\Services\PromoCodeService;
use App\Services\SubscriptionPromoQuote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionController extends Controller
{
    public function __construct(
        private BalanceService $balanceService,
        private PromoCodeService $promoCodeService
    ) {}

    public function index()
    {
        $user = Auth::user();
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $activeSubscription = $user->activeSubscription();
        $lastExpiredSubscription = $user->lastExpiredSubscription();

        return view('subscription.index', [
            'plans' => $plans,
            'activeSubscription' => $activeSubscription,
            'lastExpiredSubscription' => $lastExpiredSubscription,
            'balance' => $user->balance,
            'promoPlanOptions' => $this->buildPromoPlanOptions($user, $plans, $activeSubscription),
        ]);
    }

    /**
     * @param  Collection<int, SubscriptionPlan>  $plans
     * @return list<array{id: int, label: string, context: string, base_price: float}>
     */
    private function buildPromoPlanOptions(User $user, $plans, ?UserSubscription $activeSubscription): array
    {
        $options = [];
        foreach ($plans as $plan) {
            if ($activeSubscription) {
                if ($plan->sort_order <= $activeSubscription->plan->sort_order) {
                    continue;
                }
                $base = $this->computeUpgradePriceDiff($activeSubscription, $plan);
                if ($base === null || $base <= 0) {
                    continue;
                }
                $options[] = [
                    'id' => (int) $plan->id,
                    'label' => $plan->getTranslatedName().' — '.__('messages.subscription.promo.context_upgrade'),
                    'context' => PromoCodeUsage::CONTEXT_UPGRADE,
                    'base_price' => $base,
                ];
            } else {
                $options[] = [
                    'id' => (int) $plan->id,
                    'label' => $plan->getTranslatedName().' — '.__('messages.subscription.promo.context_purchase'),
                    'context' => PromoCodeUsage::CONTEXT_PURCHASE,
                    'base_price' => (float) $plan->price_usd,
                ];
            }
        }

        return $options;
    }

    private function computeUpgradePriceDiff(UserSubscription $active, SubscriptionPlan $newPlan): ?float
    {
        $currentPlan = $active->plan;
        if ($newPlan->sort_order <= $currentPlan->sort_order) {
            return null;
        }
        $daysRemaining = max(0, now()->diffInDays($active->ends_at, false));
        $currentDuration = max(1, (int) $currentPlan->duration_days);
        $fullDiff = (float) $newPlan->price_usd - (float) $currentPlan->price_usd;
        $priceDiff = round($fullDiff * ($daysRemaining / $currentDuration), 2);

        return $priceDiff > 0 ? $priceDiff : null;
    }

    public function validatePromo(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'promo_code' => 'nullable|string|max:64',
            'context' => 'required|in:purchase,upgrade',
        ]);

        $user = Auth::user();
        $plan = SubscriptionPlan::where('id', $data['plan_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $base = null;
        if ($data['context'] === PromoCodeUsage::CONTEXT_PURCHASE) {
            if ($user->activeSubscription()) {
                return response()->json([
                    'valid' => false,
                    'error_code' => 'invalid_context',
                    'message' => __('messages.subscription.promo.errors.invalid_context'),
                ], 422);
            }
            $base = (float) $plan->price_usd;
        } else {
            $active = $user->activeSubscription();
            if (! $active) {
                return response()->json([
                    'valid' => false,
                    'error_code' => 'invalid_context',
                    'message' => __('messages.subscription.promo.errors.invalid_context'),
                ], 422);
            }
            $base = $this->computeUpgradePriceDiff($active, $plan);
            if ($base === null) {
                return response()->json([
                    'valid' => false,
                    'error_code' => 'upgrade_not_allowed',
                    'message' => __('messages.subscription.promo.errors.upgrade_not_allowed'),
                ], 422);
            }
        }

        $quote = $this->promoCodeService->quote(
            $data['promo_code'] ?? null,
            $user,
            $plan,
            $data['context'],
            $base
        );

        $payload = $quote->toArray();
        $payload['message'] = $quote->valid
            ? ($quote->promoCode ? __('messages.subscription.promo.applied') : '')
            : __('messages.subscription.promo.errors.'.$quote->errorCode);

        return response()->json($payload);
    }

    public function purchase(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'promo_code' => 'nullable|string|max:64',
        ]);

        $user = Auth::user();
        if ($user->activeSubscription()) {
            return redirect()->route('subscription.index')->with('error', __('messages.subscription.promo.errors.invalid_context'));
        }

        $plan = SubscriptionPlan::where('id', $data['plan_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $basePrice = (float) $plan->price_usd;

        try {
            DB::transaction(function () use ($user, $plan, $basePrice, $data) {
                $quote = $this->promoCodeService->quoteLocked(
                    $data['promo_code'] ?? null,
                    $user,
                    $plan,
                    PromoCodeUsage::CONTEXT_PURCHASE,
                    $basePrice
                );
                $this->assertQuotePayable($quote, $basePrice);

                if ($this->promoCodeService->shouldChargeBalance($quote->finalAmount)) {
                    $this->balanceService->decrementBalance(User::class, (int) $user->id, $quote->finalAmount);
                }

                $startsAt = now();
                $endsAt = $startsAt->copy()->addDays((int) $plan->duration_days);

                $subscription = UserSubscription::create([
                    'user_id' => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => 'active',
                    'provider_ref' => 'balance',
                ]);

                $orderId = 'sub-'.uniqid();
                ShortlinkTransaction::create([
                    'order_id' => $orderId,
                    'amount' => -$quote->finalAmount,
                    'currency' => 'USD',
                    'status' => 'paid',
                    'identifier' => 'user:'.$user->id,
                    'count' => 0,
                    'url' => null,
                    'provider_ref' => 'subscription:'.$plan->slug,
                    'payment_kind' => ShortlinkTransaction::KIND_SUBSCRIPTION,
                    'promo_code_id' => $quote->promoCode?->id,
                    'subscription_gross_amount' => $quote->originalAmount,
                    'subscription_discount_amount' => $quote->discountAmount > 0 ? $quote->discountAmount : null,
                ]);

                if ($quote->promoCode) {
                    PromoCodeUsage::create([
                        'promo_code_id' => $quote->promoCode->id,
                        'user_id' => $user->id,
                        'user_subscription_id' => $subscription->id,
                        'subscription_plan_id' => $plan->id,
                        'context' => PromoCodeUsage::CONTEXT_PURCHASE,
                        'shortlink_transaction_order_id' => $orderId,
                        'original_amount' => $quote->originalAmount,
                        'discount_amount' => $quote->discountAmount,
                        'final_amount' => $quote->finalAmount,
                    ]);
                }
            });
        } catch (InsufficientBalanceException) {
            return redirect()
                ->route('subscription.index')
                ->with('error', __('messages.subscription.promo.insufficient_balance'));
        } catch (ValidationException $e) {
            return redirect()
                ->route('subscription.index')
                ->withErrors($e->errors())
                ->withInput();
        }

        return redirect()
            ->route('subscription.index')
            ->with('success', __('messages.subscription.purchase_success', [
                'name' => $plan->getTranslatedName(),
                'date' => now()->addDays((int) $plan->duration_days)->format('M j, Y'),
            ]));
    }

    public function upgrade(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'promo_code' => 'nullable|string|max:64',
        ]);

        $user = Auth::user();
        $activeSubscription = $user->activeSubscription();
        if (! $activeSubscription) {
            return redirect()->route('subscription.index')->with('error', __('messages.subscription.promo.errors.no_active_upgrade'));
        }

        $newPlan = SubscriptionPlan::where('id', $data['plan_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $currentPlan = $activeSubscription->plan;
        if ($newPlan->sort_order <= $currentPlan->sort_order) {
            return redirect()->route('subscription.index')->with('error', __('messages.subscription.promo.errors.upgrade_not_allowed'));
        }

        $priceDiff = $this->computeUpgradePriceDiff($activeSubscription, $newPlan);
        if ($priceDiff === null || $priceDiff <= 0) {
            return redirect()->route('subscription.index')->with('error', __('messages.subscription.promo.errors.upgrade_not_allowed'));
        }

        try {
            DB::transaction(function () use ($user, $activeSubscription, $newPlan, $priceDiff, $data) {
                $quote = $this->promoCodeService->quoteLocked(
                    $data['promo_code'] ?? null,
                    $user,
                    $newPlan,
                    PromoCodeUsage::CONTEXT_UPGRADE,
                    $priceDiff
                );
                $this->assertQuotePayable($quote, $priceDiff);

                if ($this->promoCodeService->shouldChargeBalance($quote->finalAmount)) {
                    $this->balanceService->decrementBalance(User::class, (int) $user->id, $quote->finalAmount);
                }

                $activeSubscription->update([
                    'subscription_plan_id' => $newPlan->id,
                ]);

                $orderId = 'upg-'.uniqid();
                ShortlinkTransaction::create([
                    'order_id' => $orderId,
                    'amount' => -$quote->finalAmount,
                    'currency' => 'USD',
                    'status' => 'paid',
                    'identifier' => 'user:'.$user->id,
                    'count' => 0,
                    'url' => null,
                    'provider_ref' => 'upgrade:'.$newPlan->slug,
                    'payment_kind' => ShortlinkTransaction::KIND_SUBSCRIPTION_UPGRADE,
                    'promo_code_id' => $quote->promoCode?->id,
                    'subscription_gross_amount' => $quote->originalAmount,
                    'subscription_discount_amount' => $quote->discountAmount > 0 ? $quote->discountAmount : null,
                ]);

                if ($quote->promoCode) {
                    PromoCodeUsage::create([
                        'promo_code_id' => $quote->promoCode->id,
                        'user_id' => $user->id,
                        'user_subscription_id' => $activeSubscription->id,
                        'subscription_plan_id' => $newPlan->id,
                        'context' => PromoCodeUsage::CONTEXT_UPGRADE,
                        'shortlink_transaction_order_id' => $orderId,
                        'original_amount' => $quote->originalAmount,
                        'discount_amount' => $quote->discountAmount,
                        'final_amount' => $quote->finalAmount,
                    ]);
                }
            });
        } catch (InsufficientBalanceException) {
            return redirect()
                ->route('subscription.index')
                ->with('error', __('messages.subscription.promo.insufficient_balance'));
        } catch (ValidationException $e) {
            return redirect()
                ->route('subscription.index')
                ->withErrors($e->errors())
                ->withInput();
        }

        $endsAt = $user->fresh()->activeSubscription()?->ends_at;

        return redirect()
            ->route('subscription.index')
            ->with('success', __('messages.subscription.upgrade_success', [
                'name' => $newPlan->getTranslatedName(),
                'date' => $endsAt?->format('M j, Y') ?? '',
            ]));
    }

    private function assertQuotePayable(SubscriptionPromoQuote $quote, float $basePrice): void
    {
        if (! $quote->valid) {
            throw ValidationException::withMessages([
                'promo_code' => [__('messages.subscription.promo.errors.'.$quote->errorCode)],
            ]);
        }
        if ($quote->finalAmount < 0 || $quote->finalAmount > $basePrice + 0.0001) {
            throw ValidationException::withMessages([
                'promo_code' => [__('messages.subscription.promo.errors.invalid')],
            ]);
        }
    }
}
