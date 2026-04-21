<?php

namespace App\Services;

use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class PromoCodeService
{
    public function __construct(
        private BalanceService $balanceService
    ) {}

    /**
     * Preview validation (no row locks). Use for AJAX before checkout.
     */
    public function quote(
        ?string $rawCode,
        User $user,
        SubscriptionPlan $plan,
        string $context,
        float $basePrice,
    ): SubscriptionPromoQuote {
        $code = PromoCode::normalizeCode($rawCode);
        if ($code === '') {
            return SubscriptionPromoQuote::noPromo($basePrice);
        }

        $promo = PromoCode::query()->where('code', $code)->first();
        if (! $promo) {
            return SubscriptionPromoQuote::failure('invalid', $basePrice);
        }

        return $this->evaluateLoaded($promo, $user, $plan, $context, $basePrice, false);
    }

    /**
     * Checkout: lock promo row, re-check limits, return quote (caller runs inside DB transaction).
     */
    public function quoteLocked(
        ?string $rawCode,
        User $user,
        SubscriptionPlan $plan,
        string $context,
        float $basePrice,
    ): SubscriptionPromoQuote {
        $code = PromoCode::normalizeCode($rawCode);
        if ($code === '') {
            return SubscriptionPromoQuote::noPromo($basePrice);
        }

        $promo = PromoCode::query()->where('code', $code)->lockForUpdate()->first();
        if (! $promo) {
            return SubscriptionPromoQuote::failure('invalid', $basePrice);
        }

        return $this->evaluateLoaded($promo, $user, $plan, $context, $basePrice, true);
    }

    private function evaluateLoaded(
        PromoCode $promo,
        User $user,
        SubscriptionPlan $plan,
        string $context,
        float $basePrice,
        bool $locked,
    ): SubscriptionPromoQuote {
        if (! $promo->is_active) {
            return SubscriptionPromoQuote::failure('inactive', $basePrice);
        }

        if ($promo->isExpired()) {
            return SubscriptionPromoQuote::failure('expired', $basePrice);
        }

        if (! $promo->appliesToPlanId((int) $plan->id)) {
            return SubscriptionPromoQuote::failure('plan_not_applicable', $basePrice);
        }

        if ($promo->first_purchase_only) {
            $hasHistory = $user->subscriptions()->exists();
            if ($hasHistory || $context !== PromoCodeUsage::CONTEXT_PURCHASE) {
                return SubscriptionPromoQuote::failure('first_purchase_only', $basePrice);
            }
        }

        $usesCount = $locked
            ? (int) DB::table('promo_code_usages')->where('promo_code_id', $promo->id)->lockForUpdate()->count()
            : (int) $promo->usages()->count();
        if ($promo->max_uses !== null && $usesCount >= (int) $promo->max_uses) {
            return SubscriptionPromoQuote::failure('exhausted', $basePrice);
        }

        if ($promo->once_per_user) {
            $already = $promo->usages()->where('user_id', $user->id)->exists();
            if ($already) {
                return SubscriptionPromoQuote::failure('already_used', $basePrice);
            }
        }

        $discount = $this->computeDiscountAmount($basePrice, $promo);
        $discount = min($discount, $basePrice);
        $final = max(0.0, round($basePrice - $discount, 2));

        return SubscriptionPromoQuote::success($promo, $basePrice, $discount, $final);
    }

    private function computeDiscountAmount(float $base, PromoCode $promo): float
    {
        if ($promo->discount_type === PromoCode::DISCOUNT_PERCENT) {
            $pct = min(100.0, max(0.0, (float) $promo->discount_value));

            return round($base * ($pct / 100.0), 2);
        }

        if ($promo->discount_type === PromoCode::DISCOUNT_FIXED) {
            return min($base, max(0.0, round((float) $promo->discount_value, 2)));
        }

        return 0.0;
    }

    /**
     * True if final amount is greater than zero (balance debit required).
     */
    public function shouldChargeBalance(float $finalAmount): bool
    {
        return $this->balanceService->compareAmounts($finalAmount, '0') > 0;
    }
}
