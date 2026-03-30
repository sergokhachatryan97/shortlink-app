<?php

namespace App\Services;

use App\Models\ShortlinkLink;
use App\Models\Order;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\DB;

class ShortlinkEntitlementService
{
    /** One-time free links per registered user (and legacy guest rules in ShortlinkController). */
    public const FREE_TRIAL_LIMIT = 50;

    public function identifierForUser(User $user): string
    {
        return 'user:' . $user->id;
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
     * Same rules as ShortlinkController::generate for logged-in users: active plan links
     * and/or the 50-link free trial reduce the billable quantity for Panel API orders.
     *
     * @return array{effective_quantity: int, free_quantity: int, paid_quantity: int, user_subscription_id: int|null}
     */
    public function computePanelOrderQuota(User $user, int $requestedQuantity, string $ip): array
    {
        $sub = $user->activeSubscription();
        if ($sub) {
            return $this->computeQuotaForSubscription($sub, $requestedQuantity);
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
        ];
    }

    /**
     * @return array{effective_quantity: int, free_quantity: int, paid_quantity: int, user_subscription_id: int|null}
     */
    private function computeQuotaForSubscription(UserSubscription $sub, int $count): array
    {
        $plan = $sub->plan;
        $currentCount = ShortlinkLink::where('user_subscription_id', $sub->id)->count();
        $freeInPlan = $plan->isUnlimited() ? $count : max(0, (int) $plan->links_limit - $currentCount);
        $effectiveCount = $plan->isUnlimited()
            ? $count
            : min($count, (int) $plan->links_limit - $currentCount);

        if ($effectiveCount <= 0) {
            return [
                'effective_quantity' => $count,
                'free_quantity' => 0,
                'paid_quantity' => $count,
                'user_subscription_id' => $sub->id,
            ];
        }

        $paidQty = $effectiveCount - min($effectiveCount, $freeInPlan);

        return [
            'effective_quantity' => $effectiveCount,
            'free_quantity' => $effectiveCount - $paidQty,
            'paid_quantity' => $paidQty,
            'user_subscription_id' => $sub->id,
        ];
    }

    /**
     * Mirror web bookkeeping: free-trial rows + ShortlinkLink for subscription or paid links.
     */
    public function persistPanelOrderSideEffects(Order $order, User $user, string $ip): void
    {
        $meta = $order->metadata ?? [];
        $links = $meta['generated_links'] ?? [];
        if (!is_array($links) || $links === []) {
            return;
        }

        $freeQty = (int) ($meta['panel_free_quantity'] ?? 0);
        $paidQty = (int) ($meta['panel_paid_quantity'] ?? 0);
        $subId = isset($meta['panel_user_subscription_id']) ? (int) $meta['panel_user_subscription_id'] : null;
        if ($subId <= 0) {
            $subId = null;
        }

        if ($freeQty > 0 && $subId === null) {
            $this->recordFreeTrialUse($this->identifierForUser($user), $ip, $freeQty);
        }

        $shouldPersistLinks = $subId !== null || $paidQty > 0;
        if (!$shouldPersistLinks) {
            return;
        }

        $batchId = 'panel-api-' . $order->id;
        foreach ($links as $i => $shortUrl) {
            ShortlinkLink::query()->create([
                'user_id' => $user->id,
                'user_subscription_id' => $subId,
                'original_url' => (string) $order->link,
                'short_url' => (string) $shortUrl,
                'batch_index' => $i + 1,
                'batch_id' => $batchId,
                'expires_at' => $subId !== null ? null : now()->addDays(30),
            ]);
        }
    }
}
