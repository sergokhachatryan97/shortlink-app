@props([
    'plan',
    'pillClass' => 'cosmic-plan-feature',
])

<div {{ $attributes->merge(['class' => 'subscription-plan-pills mb-3']) }}>
    @if ($plan->isUnlimited() && $plan->hasDailyLinksLimit())
        <span class="{{ $pillClass }}">{{ __('messages.subscription.plan_unlimited_total') }}</span>
        <span class="{{ $pillClass }}">{{ __('messages.subscription.plan_vip_daily', ['count' => number_format((int) $plan->daily_links_limit)]) }}</span>
    @elseif ($plan->isUnlimited())
        <span class="{{ $pillClass }}">{{ __('messages.subscription.plan_unlimited_total') }}</span>
        <span class="{{ $pillClass }}">{{ __('messages.subscription.plan_no_daily_limit') }}</span>
    @else
        <span class="{{ $pillClass }}">{{ __('messages.subscription.plan_included_links', ['count' => number_format((int) $plan->links_limit)]) }}</span>
        <span class="{{ $pillClass }}">{{ __('messages.subscription.plan_billing_cycle', ['days' => (int) $plan->duration_days]) }}</span>
    @endif
</div>
