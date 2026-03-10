<?php

namespace App\Http\Controllers;

use App\Models\ShortlinkTransaction;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
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
        ]);
    }

    public function purchase(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $user = Auth::user();
        $plan = SubscriptionPlan::where('id', $data['plan_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $price = (float) $plan->price_usd;
        if ($user->balance < $price) {
            return redirect()
                ->route('subscription.index')
                ->with('error', 'Insufficient balance. Need $' . number_format($price, 2) . ', you have $' . number_format($user->balance, 2) . '.');
        }

        DB::transaction(function () use ($user, $plan, $price) {
            $user->decrement('balance', $price);

            $startsAt = now();
            $endsAt = $startsAt->copy()->addDays((int) $plan->duration_days);

            UserSubscription::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'active',
                'provider_ref' => 'balance',
            ]);

            ShortlinkTransaction::create([
                'order_id' => 'sub-' . uniqid(),
                'amount' => -$price,
                'currency' => 'USD',
                'status' => 'paid',
                'identifier' => 'user:' . $user->id,
                'count' => 0,
                'url' => null,
                'provider_ref' => 'subscription:' . $plan->slug,
            ]);
        });

        return redirect()
            ->route('subscription.index')
            ->with('success', 'Subscribed to ' . $plan->name . ' until ' . now()->addDays((int) $plan->duration_days)->format('M j, Y') . '.');
    }

    public function upgrade(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $user = Auth::user();
        $activeSubscription = $user->activeSubscription();
        if (!$activeSubscription) {
            return redirect()->route('subscription.index')->with('error', 'No active subscription to upgrade.');
        }

        $newPlan = SubscriptionPlan::where('id', $data['plan_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $currentPlan = $activeSubscription->plan;
        if ($newPlan->sort_order <= $currentPlan->sort_order) {
            return redirect()->route('subscription.index')->with('error', 'Can only upgrade to a higher tier plan.');
        }

        $daysRemaining = max(0, now()->diffInDays($activeSubscription->ends_at, false));
        $currentDuration = max(1, (int) $currentPlan->duration_days);
        $fullDiff = (float) $newPlan->price_usd - (float) $currentPlan->price_usd;
        $priceDiff = round($fullDiff * ($daysRemaining / $currentDuration), 2);

        if ($priceDiff <= 0) {
            return redirect()->route('subscription.index')->with('error', 'Invalid upgrade.');
        }

        if ($user->balance < $priceDiff) {
            return redirect()
                ->route('subscription.index')
                ->with('error', 'Insufficient balance. Upgrade costs $' . number_format($priceDiff, 2) . ', you have $' . number_format($user->balance, 2) . '.');
        }

        DB::transaction(function () use ($user, $activeSubscription, $newPlan, $priceDiff) {
            $user->decrement('balance', $priceDiff);

            $activeSubscription->update([
                'subscription_plan_id' => $newPlan->id,
            ]);

            ShortlinkTransaction::create([
                'order_id' => 'upg-' . uniqid(),
                'amount' => -$priceDiff,
                'currency' => 'USD',
                'status' => 'paid',
                'identifier' => 'user:' . $user->id,
                'count' => 0,
                'url' => null,
                'provider_ref' => 'upgrade:' . $newPlan->slug,
            ]);
        });

        return redirect()
            ->route('subscription.index')
            ->with('success', 'Upgraded to ' . $newPlan->name . '. Valid until ' . $activeSubscription->fresh()->ends_at->format('M j, Y') . '.');
    }
}
