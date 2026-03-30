<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ShortlinkLink;
use App\Models\ShortlinkSetting;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\DB;

class ShortlinkEntitlementService
{
    /** One-time free links per registered user (and legacy guest rules in ShortlinkController). */
    public const FREE_TRIAL_LIMIT = 50;

    public function identifierForUser(User $user): string
    {
        return 'user:'.$user->id;
    }

    public function getFreeTrialUsedCount(string $identifier, string $ip): int
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

    public function getRemainingFreeTrial(string $identifier, string $ip): int
    {
        $used = $this->getFreeTrialUsedCount($identifier, $ip);

        return max(0, self::FREE_TRIAL_LIMIT - $used);
    }

    public function recordFreeTrialUse(string $identifier, string $ip, int $count): void
    {
        if ($count <= 0) {
            return;
        }

        DB::table('shortlink_free_trial_uses')->insert([
            'identifier' => $identifier,
            'ip_address' => $ip,
            'links_count' => $count,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Same rules as ShortlinkController::generate for logged-in users: with a subscription,
     * free-trial allowance first, then plan-included links, then balance. Without a subscription,
     * free trial then balance (via computeLinkedUserPaidCharge on paid_quantity).
     *
     * @return array{effective_quantity: int, free_quantity: int, paid_quantity: int, user_subscription_id: int|null, trial_quantity_this_order: int, plan_quantity_this_order: int}
     */
    public function computePanelOrderQuota(User $user, int $requestedQuantity, string $ip): array
    {
        $sub = $user->activeSubscription();
        if ($sub) {
            return $this->computeQuotaForSubscription($sub, $requestedQuantity, $user, $ip);
        }

        $identifier = $this->identifierForUser($user);
        $remaining = $this->getRemainingFreeTrial($identifier, $ip);
        $freeQty = min($requestedQuantity, $remaining);
        $paidQty = $requestedQuantity - $freeQty;

        return [
            'effective_quantity' => $requestedQuantity,
            'free_quantity' => $freeQty,
            'paid_quantity' => $paidQty,
            'user_subscription_id' => null,
            'trial_quantity_this_order' => $freeQty,
            'plan_quantity_this_order' => 0,
        ];
    }

    /**
     * @return array{effective_quantity: int, free_quantity: int, paid_quantity: int, user_subscription_id: int|null, trial_quantity_this_order: int, plan_quantity_this_order: int}
     */
    private function computeQuotaForSubscription(UserSubscription $sub, int $count, User $user, string $ip): array
    {
        $identifier = $this->identifierForUser($user);
        $trialRemaining = $this->getRemainingFreeTrial($identifier, $ip);

        $fromTrial = min($count, $trialRemaining);
        $afterTrial = $count - $fromTrial;

        $plan = $sub->plan;
        $currentCount = ShortlinkLink::where('user_subscription_id', $sub->id)->count();

        if ($plan->isUnlimited()) {
            $fromPlan = $afterTrial;
            $fromBalance = 0;
        } else {
            $slots = max(0, (int) $plan->links_limit - $currentCount);
            $fromPlan = min($afterTrial, $slots);
            $fromBalance = $afterTrial - $fromPlan;
        }

        return [
            'effective_quantity' => $count,
            'free_quantity' => $fromTrial + $fromPlan,
            'paid_quantity' => $fromBalance,
            'user_subscription_id' => $sub->id,
            'trial_quantity_this_order' => $fromTrial,
            'plan_quantity_this_order' => $fromPlan,
        ];
    }

    /**
     * Paid link total for dashboard-linked users (matches ShortlinkController / website).
     */
    public function computeLinkedUserPaidCharge(int $paidQuantity): float
    {
        if ($paidQuantity <= 0) {
            return 0.0;
        }

        $pricePerLink = (float) ShortlinkSetting::get('price_per_link', '0.01');

        return $paidQuantity * $pricePerLink;
    }

    /**
     * Plan + quota context for Panel API (balance, add errors, add success).
     *
     * @return array{subscription: array<string, mixed>|null, free_trial: array<string, mixed>|null, subscription_expired?: array<string, mixed>|null}
     */
    public function linkedAccountPanelSummary(User $user, string $ip): array
    {
        $sub = $user->activeSubscription();
        if ($sub) {
            $plan = $sub->plan;
            $used = ShortlinkLink::query()->where('user_subscription_id', $sub->id)->count();

            return [
                'subscription' => [
                    'active' => true,
                    'plan_name' => $plan->getTranslatedName(),
                    'links_limit' => $plan->isUnlimited() ? null : (int) $plan->links_limit,
                    'links_used' => $used,
                    'links_remaining' => $plan->isUnlimited() ? null : max(0, (int) $plan->links_limit - $used),
                    'unlimited' => $plan->isUnlimited(),
                    'ends_at' => $sub->ends_at?->toIso8601String(),
                ],
                'free_trial' => null,
                'subscription_expired' => null,
            ];
        }

        $identifier = $this->identifierForUser($user);
        $remaining = $this->getRemainingFreeTrial($identifier, $ip);
        $pricePerLink = (float) ShortlinkSetting::get('price_per_link', '0.01');

        return [
            'subscription' => null,
            'free_trial' => [
                'limit' => self::FREE_TRIAL_LIMIT,
                'remaining' => $remaining,
                'price_per_link_usd' => number_format($pricePerLink, 3, '.', ''),
                'overage_billed_from_balance' => true,
                'notice' => 'If an order requests more links than your free-trial remaining, the extra links are charged from your account balance at price_per_link_usd (same rules as the website).',
            ],
            'subscription_expired' => $this->expiredSubscriptionNotice($user),
        ];
    }

    /**
     * When there is no active subscription, describe the most recent ended plan so API clients can show renewal UX.
     *
     * @return array<string, mixed>|null
     */
    public function expiredSubscriptionNotice(User $user): ?array
    {
        if ($user->activeSubscription()) {
            return null;
        }

        $ended = $user->lastExpiredSubscription();
        if (! $ended || ! $ended->plan) {
            return null;
        }

        $plan = $ended->plan;
        $timeExpired = $ended->ends_at && $ended->ends_at->lte(now());
        $reason = $ended->status !== 'active' ? 'inactive' : ($timeExpired ? 'time_expired' : 'inactive');

        return [
            'plan_name' => $plan->getTranslatedName(),
            'ended_at' => $ended->ends_at?->toIso8601String(),
            'record_status' => $ended->status,
            'reason' => $reason,
            'notice' => 'Your subscription is not active. Additional links are billed from your account balance (and free-trial quota if you still have any). Subscribe again to restore plan link allowances.',
        ];
    }

    /**
     * Per-order subscription breakdown for Panel API `add` responses.
     *
     * @param  array{effective_quantity?:int,free_quantity:int,paid_quantity:int,user_subscription_id:int|null}  $panelQuota
     * @return array<string, mixed>|null
     */
    public function subscriptionOrderSnapshot(User $user, array $panelQuota, int $requestedQuantity, bool $afterPersist = false): ?array
    {
        $subId = $panelQuota['user_subscription_id'] ?? null;
        if (! $subId) {
            return null;
        }
        $sub = $user->activeSubscription();
        if (! $sub || (int) $sub->id !== (int) $subId) {
            return null;
        }
        $plan = $sub->plan;
        $used = ShortlinkLink::query()->where('user_subscription_id', $sub->id)->count();
        $pricePerLink = (float) ShortlinkSetting::get('price_per_link', '0.01');
        $limit = $plan->isUnlimited() ? null : (int) $plan->links_limit;
        $remaining = $plan->isUnlimited() ? null : max(0, $limit - $used);
        $allowanceDepleted = ! $plan->isUnlimited() && $remaining !== null && $remaining <= 0;

        $trialThis = (int) ($panelQuota['trial_quantity_this_order'] ?? 0);
        $planThis = (int) ($panelQuota['plan_quantity_this_order'] ?? 0);
        if (! array_key_exists('trial_quantity_this_order', $panelQuota)
            && ! array_key_exists('plan_quantity_this_order', $panelQuota)) {
            $planThis = (int) ($panelQuota['free_quantity'] ?? 0);
        }

        $snapshot = [
            'plan_name' => $plan->getTranslatedName(),
            'links_limit' => $limit,
            'links_used' => $used,
            'links_remaining' => $remaining,
            'unlimited' => $plan->isUnlimited(),
            'current_period_ends_at' => $sub->ends_at?->toIso8601String(),
            'plan_allowance_depleted' => $allowanceDepleted,
            'requested_quantity' => $requestedQuantity,
            'effective_quantity' => (int) ($panelQuota['effective_quantity'] ?? $requestedQuantity),
            'included_by_free_trial_this_order' => $trialThis,
            'included_by_plan_this_order' => $planThis,
            'paid_quantity_this_order' => (int) $panelQuota['paid_quantity'],
            'price_per_link_usd' => number_format($pricePerLink, 3, '.', ''),
        ];

        if ($allowanceDepleted && (int) $panelQuota['paid_quantity'] > 0) {
            $snapshot['notice'] = 'Plan link allowance is used up for this billing period; this portion is charged from your balance at price_per_link.';
        }

        if ($afterPersist) {
            $usedAfter = ShortlinkLink::query()->where('user_subscription_id', $sub->id)->count();
            $snapshot['links_used'] = $usedAfter;
            $snapshot['links_remaining'] = $plan->isUnlimited() ? null : max(0, (int) $plan->links_limit - $usedAfter);
            $snapshot['plan_allowance_depleted'] = ! $plan->isUnlimited()
                && (int) $plan->links_limit <= $usedAfter;
        }

        return $snapshot;
    }

    /**
     * Mirror web bookkeeping: free-trial rows + ShortlinkLink for subscription or paid links.
     */
    public function persistPanelOrderSideEffects(Order $order, User $user, string $ip): void
    {
        $meta = $order->metadata ?? [];
        $links = $meta['generated_links'] ?? [];
        if (! is_array($links) || $links === []) {
            return;
        }

        $freeQty = (int) ($meta['panel_free_quantity'] ?? 0);
        $paidQty = (int) ($meta['panel_paid_quantity'] ?? 0);
        $subId = isset($meta['panel_user_subscription_id']) ? (int) $meta['panel_user_subscription_id'] : null;
        if ($subId <= 0) {
            $subId = null;
        }

        $trialInOrder = array_key_exists('panel_trial_quantity_this_order', $meta)
            ? (int) $meta['panel_trial_quantity_this_order']
            : ($subId === null ? $freeQty : 0);
        $planInOrder = array_key_exists('panel_plan_quantity_this_order', $meta)
            ? (int) $meta['panel_plan_quantity_this_order']
            : ($subId !== null ? $freeQty - $trialInOrder : 0);

        if ($trialInOrder > 0) {
            $this->recordFreeTrialUse($this->identifierForUser($user), $ip, $trialInOrder);
        }

        $shouldPersistLinks = $subId !== null || $paidQty > 0 || $trialInOrder > 0 || $planInOrder > 0;
        if (! $shouldPersistLinks) {
            return;
        }

        $batchId = 'panel-api-'.$order->id;
        $n = count($links);
        foreach ($links as $i => $shortUrl) {
            if ($i < $trialInOrder) {
                $rowSubId = null;
                $expiresAt = now()->addDays(30);
            } elseif ($i < $trialInOrder + $planInOrder) {
                $rowSubId = $subId;
                $expiresAt = null;
            } else {
                $rowSubId = $subId;
                $expiresAt = $subId !== null ? null : now()->addDays(30);
            }

            ShortlinkLink::query()->create([
                'user_id' => $user->id,
                'user_subscription_id' => $rowSubId,
                'original_url' => (string) $order->link,
                'short_url' => (string) $shortUrl,
                'batch_index' => $i + 1,
                'batch_id' => $batchId,
                'expires_at' => $expiresAt,
            ]);
        }
    }
}
