@extends('layouts.app')

@section('title', 'Billing')

@section('content')
<div class="cosmic-page-section">
    <div class="container cosmic-container" style="max-width: 960px;">
        <div class="cosmic-page-header mb-4">
            <h1 class="cosmic-page-title">Billing</h1>
            <p class="cosmic-page-subtitle mb-0">Unlock more trusted shortlinks before your subscription ends.</p>
        </div>

        @if (session('success'))
            <div class="cosmic-alert cosmic-alert-success mb-4">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="cosmic-alert cosmic-alert-danger mb-4">{{ session('error') }}</div>
        @endif

        @if ($activeSubscription)
            <div class="cosmic-alert cosmic-alert-success mb-4 d-flex align-items-center gap-2">
                <span class="cosmic-alert-icon">✓</span>
                <div>
                    <strong>Active plan: {{ $activeSubscription->plan->name }}</strong> until {{ $activeSubscription->ends_at->format('M j, Y') }}
                    @if ($activeSubscription->provider_ref === 'balance')
                        <span class="cosmic-badge ms-2">Paid with balance</span>
                    @endif
                </div>
            </div>
        @elseif ($lastExpiredSubscription)
            <div class="cosmic-alert cosmic-alert-warning mb-4">
                <strong>Subscription expired.</strong> Your plan ({{ $lastExpiredSubscription->plan->name }}) ended on {{ $lastExpiredSubscription->ends_at->format('M j, Y') }}. Purchase a new plan with balance to continue.
            </div>
        @endif

        <div class="cosmic-balance-card p-4 mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="dropdown">
                        <button class="btn cosmic-btn-add dropdown-toggle" type="button" data-bs-toggle="dropdown">Add</button>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item" href="{{ route('balance.index') }}">Add funds</a></li>
                        </ul>
                    </div>
                    <div>
                        <span class="cosmic-text-muted">Your balance:</span>
                        <strong class="cosmic-balance-amount ms-1">${{ number_format($balance ?? 0, 2) }} USD</strong>
                    </div>
                </div>
                <a href="{{ route('balance.index') }}" class="btn cosmic-btn-primary">View Transactions</a>
            </div>
        </div>

        @php $maxSortOrder = $plans->max('sort_order'); @endphp
        <div class="row g-4 mb-4">
            @foreach($plans as $plan)
            @php
                $hasActivePlan = (bool) $activeSubscription;
                $isCurrentPlan = $hasActivePlan && $activeSubscription->plan->id === $plan->id;
                $canUpgrade = $hasActivePlan && $plan->sort_order > $activeSubscription->plan->sort_order;
                $upgradePriceDiff = $canUpgrade ? (float) $plan->price_usd - (float) $activeSubscription->plan->price_usd : 0;
                $canAffordUpgrade = $canUpgrade && ($balance ?? 0) >= $upgradePriceDiff;
                $canBuyWithBalance = !$hasActivePlan && ($balance ?? 0) >= (float) $plan->price_usd;
                $isRecommended = strtolower($plan->slug ?? '') === 'pro';
                $iconClass = match(strtolower($plan->slug ?? '')) {
                    'starter' => 'icon-lightning',
                    'vip' => 'icon-star',
                    default => 'icon-check',
                };
            @endphp
            <div class="col-md-4">
                <div class="cosmic-plan-card {{ $isCurrentPlan ? 'cosmic-plan-current' : '' }} {{ $isRecommended ? 'cosmic-plan-recommended' : '' }}">
                    @if ($isRecommended)
                    <div class="cosmic-plan-badge">★ Recommended</div>
                    @endif
                    <div class="cosmic-plan-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="cosmic-plan-icon {{ $iconClass }}">
                                @if ($iconClass === 'icon-lightning')
                                <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M7 2v11h3v9l7-12h-4l4-8z"/></svg>
                                @elseif ($iconClass === 'icon-star')
                                <svg width="28" height="28" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                @else
                                <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                @endif
                            </div>
                            <h5 class="cosmic-plan-name mb-0">{{ $plan->name }}</h5>
                        </div>
                        <p class="cosmic-plan-desc mb-3">
                            @if ($plan->isUnlimited())
                                <strong>Unlimited links</strong><br>
                                <span class="cosmic-text-muted">until subscription ends</span>
                            @else
                                {{ $plan->description }}
                            @endif
                        </p>
                        <p class="cosmic-plan-price mb-3">${{ number_format($plan->price_usd, 2) }}{{ strtolower($plan->slug ?? '') === 'vip' ? '/yr' : '/mo' }}</p>

                        @if ($isCurrentPlan)
                            <button type="button" class="btn cosmic-btn-plan w-100" disabled>Current Plan</button>
                        @elseif ($canUpgrade)
                            <form method="POST" action="{{ route('subscription.upgrade') }}">
                                @csrf
                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                <button type="submit" class="btn w-100 {{ $canAffordUpgrade ? 'cosmic-btn-primary' : 'cosmic-btn-disabled' }}" {{ !$canAffordUpgrade ? 'disabled' : '' }}>
                                    Upgrade to {{ $plan->name }}
                                </button>
                                @if ($canAffordUpgrade && $upgradePriceDiff > 0)
                                <p class="cosmic-pay-today small mt-2 mb-0 text-center">Pay ${{ number_format($upgradePriceDiff, 2) }} today</p>
                                @endif
                            </form>
                        @elseif ($hasActivePlan)
                            <button type="button" class="btn cosmic-btn-disabled w-100" disabled>Downgrade not available</button>
                        @else
                            <form method="POST" action="{{ route('subscription.purchase') }}">
                                @csrf
                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                <button type="submit" class="btn w-100 {{ $canBuyWithBalance ? ($isRecommended ? 'cosmic-btn-primary' : 'cosmic-btn-plan') : 'cosmic-btn-disabled' }}" {{ !$canBuyWithBalance ? 'disabled' : '' }}>
                                    Choose {{ $plan->name }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="cosmic-billing-footer mt-4 pt-3">
            <p class="cosmic-text-muted small mb-2">When your subscription ends, generated links are deleted. Renew now to keep your short links active.</p>
            <a href="{{ route('links.download') }}" class="cosmic-link">Download your CSV before expiry.</a>
        </div>
    </div>
</div>

@push('styles')
<style>
.cosmic-page-section {
    min-height: calc(100vh - var(--navbar-height, 64px) - 80px);
    background: #0a0a12 url('{{ asset('images/hero-bg.png') }}') no-repeat center center;
    background-size: cover;
    margin: -1.5rem 0 0;
    padding: 2rem 1rem 3rem;
    position: relative;
}
.cosmic-page-section::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(10,10,18,0.75) 0%, rgba(10,10,18,0.9) 100%);
    pointer-events: none;
}
.cosmic-container { position: relative; z-index: 1; }
.cosmic-page-title { font-size: 1.75rem; font-weight: 700; color: #fff; }
.cosmic-page-subtitle { color: rgba(255,255,255,0.7); font-size: 0.9375rem; }
.cosmic-text-muted { color: rgba(255,255,255,0.65); }
.cosmic-alert { border-radius: 12px; padding: 1rem 1.25rem; }
.cosmic-alert-success { background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.4); color: #86efac; }
.cosmic-alert-danger { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.4); color: #fca5a5; }
.cosmic-alert-warning { background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.4); color: #fcd34d; }
.cosmic-alert-icon { width: 24px; height: 24px; background: rgba(34,197,94,0.5); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
.cosmic-badge { font-size: 0.75rem; padding: 2px 8px; background: rgba(255,255,255,0.2); border-radius: 6px; }
.cosmic-balance-card {
    background: rgba(30, 30, 45, 0.7);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
}
.cosmic-balance-amount { color: #fff; font-size: 1.25rem; }
.cosmic-btn-add {
    background: rgba(30,30,45,0.9);
    border: 1px solid rgba(255,255,255,0.2);
    color: #fff;
    border-radius: 8px;
    font-weight: 600;
    padding: 6px 14px;
}
.cosmic-btn-add:hover { background: rgba(40,40,60,0.9); color: #fff; }
.cosmic-btn-primary {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border: none;
    color: #fff !important;
    font-weight: 600;
    padding: 10px 20px;
    border-radius: 10px;
}
.cosmic-btn-primary:hover { opacity: 0.95; color: #fff !important; }
.cosmic-plan-card {
    background: rgba(30, 30, 45, 0.7);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    position: relative;
    overflow: visible;
}
.cosmic-plan-recommended {
    border-color: rgba(167,139,250,0.5);
    box-shadow: 0 0 20px rgba(167,139,250,0.2);
}
.cosmic-plan-current { border-color: rgba(34,197,94,0.5); }
.cosmic-plan-badge {
    position: absolute;
    top: -8px;
    right: 16px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 20px;
}
.cosmic-plan-body { display: flex; flex-direction: column; }
.cosmic-plan-icon {
    width: 48px; height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    flex-shrink: 0;
}
.cosmic-plan-icon.icon-lightning { background: rgba(255,255,255,0.2); }
.cosmic-plan-icon.icon-check { background: rgba(255,255,255,0.2); }
.cosmic-plan-recommended .cosmic-plan-icon { background: rgba(167,139,250,0.4); }
.cosmic-plan-icon.icon-star { background: rgba(255,255,255,0.2); }
.cosmic-plan-name { color: #fff; font-weight: 600; }
.cosmic-plan-desc { color: rgba(255,255,255,0.8); font-size: 0.9375rem; line-height: 1.5; }
.cosmic-plan-price { font-size: 1.25rem; font-weight: 700; color: #fff; }
.cosmic-plan-recommended .cosmic-plan-price { font-size: 1.5rem; color: #a78bfa; }
.cosmic-btn-plan {
    background: rgba(30,30,45,0.9);
    border: 1px solid rgba(255,255,255,0.2);
    color: #fff;
    border-radius: 10px;
    font-weight: 600;
    padding: 10px 16px;
}
.cosmic-btn-plan:hover { background: rgba(40,40,60,0.9); color: #fff; }
.cosmic-btn-disabled {
    background: rgba(30,30,45,0.5);
    border: 1px solid rgba(255,255,255,0.1);
    color: rgba(255,255,255,0.5);
    border-radius: 10px;
    font-weight: 600;
    padding: 10px 16px;
}
.cosmic-pay-today { color: rgba(255,255,255,0.6); }
.cosmic-billing-footer { border-top: 1px solid rgba(255,255,255,0.08); }
.cosmic-link { color: #a78bfa; text-decoration: none; font-size: 0.875rem; }
.cosmic-link:hover { color: #c4b5fd; }
.dropdown-menu-dark { background: rgba(30,30,45,0.95); border: 1px solid rgba(255,255,255,0.1); }
.dropdown-menu-dark .dropdown-item { color: rgba(255,255,255,0.9); }
.dropdown-menu-dark .dropdown-item:hover { background: rgba(255,255,255,0.1); color: #fff; }
</style>
@endpush
@endsection
