@extends('layouts.app')

@section('title', 'Billing')

@section('content')
<div class="container billing-page" style="max-width: 960px;">
    <div class="page-header mb-4">
        <h1 class="page-title">Billing</h1>
        <p class="page-subtitle">Unlock more links and store them until your plan ends.</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 mb-4" style="border-radius: 12px;">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger border-0 mb-4" style="border-radius: 12px;">{{ session('error') }}</div>
    @endif

    @if ($activeSubscription)
        <div class="active-plan-banner mb-4">
            <span class="active-plan-icon">✓</span>
            <div class="flex-grow-1">
                <strong>Active plan: {{ $activeSubscription->plan->name }} until {{ $activeSubscription->ends_at->format('M j, Y') }}</strong>
                @if ($activeSubscription->provider_ref === 'balance')
                    <span class="badge-payment ms-2">Paid with balance</span>
                @endif
            </div>
        </div>
    @elseif ($lastExpiredSubscription)
        <div class="alert alert-warning border-0 mb-4" style="border-radius: 12px;">
            <strong>Subscription expired.</strong> Your plan ({{ $lastExpiredSubscription->plan->name }}) ended on {{ $lastExpiredSubscription->ends_at->format('M j, Y') }}. Purchase a new plan with balance or payment to continue.
        </div>
    @endif

    <div class="balance-section mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <span class="text-muted">Your balance:</span>
                <strong class="fs-5 ms-1">${{ number_format($balance ?? 0, 2) }} USD</strong>
            </div>
            <a href="{{ route('balance.index') }}" class="btn btn-view-transactions text-nowrap">View Transactions</a>
        </div>
        <div class="balance-add-row">
            <div class="dropdown">
                <button class="btn btn-add-dropdown dropdown-toggle" type="button" data-bs-toggle="dropdown">Add</button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('balance.index') }}">Add funds</a></li>
                </ul>
            </div>
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
            $isRecommended = $plan->sort_order === $maxSortOrder && $maxSortOrder > 1;
            $iconClass = match(strtolower($plan->slug ?? '')) {
                'starter' => 'icon-lightning',
                'unlimited' => 'icon-star',
                default => 'icon-check',
            };
        @endphp
        <div class="col-md-4">
            <div class="plan-card card h-100 {{ $isCurrentPlan ? 'plan-card-current' : '' }} {{ $isRecommended ? 'plan-card-recommended' : '' }}">
                @if ($isRecommended)
                <div class="plan-recommended-badge">
                    <span class="recommended-star">★</span> Recommended
                </div>
                @endif
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="plan-icon {{ $iconClass }} flex-shrink-0 {{ $isRecommended ? 'icon-unlimited' : '' }}">
                            @if ($iconClass === 'icon-lightning')
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M7 2v11h3v9l7-12h-4l4-8z"/></svg>
                            @elseif ($iconClass === 'icon-star')
                            <svg width="28" height="28" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            @else
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                            @endif
                        </div>
                        <h5 class="card-title fw-bold mb-0 plan-name">{{ $plan->name }}</h5>
                    </div>
                    <p class="plan-desc mb-3">
                        @if ($plan->isUnlimited())
                            <strong class="plan-feature-bold">Unlimited links</strong><br>
                            <span class="text-muted">Until subscription active</span>
                        @else
                            {{ $plan->description }}
                        @endif
                    </p>
                    <p class="mb-3 fw-bold plan-price {{ $isRecommended ? 'plan-price-recommended' : '' }}">${{ number_format($plan->price_usd, 2) }}/mo</p>

                    @if ($isCurrentPlan)
                        <div class="mt-auto">
                            <button type="button" class="btn w-100 btn-current-plan text-nowrap" disabled>Current Plan</button>
                        </div>
                    @elseif ($canUpgrade)
                        <form method="POST" action="{{ route('subscription.upgrade') }}" class="mt-auto">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            <button type="submit" class="btn w-100 btn-upgrade text-nowrap {{ !$canAffordUpgrade ? 'btn-disabled' : '' }}" {{ !$canAffordUpgrade ? 'disabled' : '' }}>
                                Upgrade to {{ $plan->name }}
                            </button>
                            @if ($canAffordUpgrade && $upgradePriceDiff > 0)
                            <p class="plan-pay-today small mt-2 mb-0 text-center">Pay ${{ number_format($upgradePriceDiff, 2) }} today</p>
                            @endif
                        </form>
                    @elseif ($hasActivePlan)
                        <div class="mt-auto">
                            <button type="button" class="btn w-100 btn-choose-disabled text-nowrap" disabled>Downgrade not available</button>
                        </div>
                    @else
                        <form method="POST" action="{{ route('subscription.purchase') }}" class="mt-auto">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            <button type="submit" class="btn w-100 text-nowrap {{ $canBuyWithBalance ? 'btn-choose-plan' : 'btn-choose-disabled' }}" {{ !$canBuyWithBalance ? 'disabled' : '' }}>
                                Choose {{ $plan->name }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="billing-footer">
        <p class="text-muted small mb-2">When your subscription ends, generated links are deleted. Renew now to keep your short links active.</p>
        <div class="d-flex flex-wrap align-items-center gap-3">
            <a href="{{ route('links.download') }}" class="text-decoration-none" style="color: var(--brand); font-size: 0.875rem;">Download your CSV before expiry.</a>
            <span class="text-muted" style="font-size: 0.75rem; opacity: 0.8;">Est. additional tax 0% per files</span>
        </div>
    </div>
</div>

@push('styles')
<style>
.billing-page {
    padding-bottom: 2rem;
    background: #f8fafc;
}
.active-plan-banner {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border: 1px solid #a7f3d0;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.active-plan-icon {
    width: 32px; height: 32px;
    background: #10b981;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1rem;
    flex-shrink: 0;
}
.badge-payment {
    font-size: 0.75rem;
    padding: 2px 8px;
    background: rgba(255,255,255,0.8);
    border-radius: 6px;
    color: #065f46;
}
.balance-section {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    padding: 1rem 1.25rem;
}
.btn-add-dropdown {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white !important;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    padding: 6px 14px;
    font-size: 0.875rem;
}
.btn-add-dropdown:hover { color: white !important; opacity: 0.9; }
.balance-add-row { display: block; }
.btn-view-transactions {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white !important;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    padding: 10px 20px;
}
.btn-view-transactions:hover { color: white !important; opacity: 0.95; }
.plan-card {
    background: #fff;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100' preserveAspectRatio='none'%3E%3Cpath fill='%23f0f4ff' fill-opacity='0.5' d='M0 30 Q25 20 50 30 T100 30 L100 100 L0 100 Z'/%3E%3Cpath fill='%23f5f0ff' fill-opacity='0.4' d='M0 40 Q25 50 50 40 T100 40 L100 100 L0 100 Z'/%3E%3C/svg%3E");
    background-size: 100% 100%;
    background-position: 0 0;
    background-repeat: no-repeat;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,.05);
    position: relative;
    overflow: visible;
}
.plan-card-current {
    border-color: #86efac;
}
.plan-card-recommended {
    background-color: #fff;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100' preserveAspectRatio='none'%3E%3Cpath fill='%23ede9fe' fill-opacity='0.8' d='M0 30 Q25 20 50 30 T100 30 L100 100 L0 100 Z'/%3E%3Cpath fill='%23e9d5ff' fill-opacity='0.5' d='M0 40 Q30 55 50 40 T100 40 L100 100 L0 100 Z'/%3E%3C/svg%3E");
    background-size: 100% 100%;
    background-position: 0 0;
    background-repeat: no-repeat;
    border-color: #c4b5fd;
    box-shadow: 0 4px 20px rgba(99, 102, 241, 0.2), 0 0 0 1px rgba(139, 92, 246, 0.1);
}
.plan-recommended-badge {
    position: absolute;
    top: -8px;
    right: 16px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    gap: 4px;
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
}
.recommended-star { color: #fcd34d; }
.plan-icon {
    width: 48px; height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.plan-icon.icon-lightning { background: #93c5fd; color: white !important; }
.plan-icon.icon-check { background: #93c5fd; color: white !important; }
.plan-icon.icon-star { background: #c4b5fd; color: white !important; }
.plan-icon.icon-unlimited { background: #a78bfa; color: white !important; }
.plan-card-recommended .plan-name { color: #1e293b; }
.plan-card-recommended .plan-desc { color: #64748b; }
.plan-card-recommended .plan-feature-bold { color: #1e293b; }
.plan-price { font-size: 1.25rem; color: #1e293b; }
.plan-price-recommended { font-size: 1.5rem; color: #6366f1; }
.plan-card-recommended .plan-pay-today { color: #64748b; }
.plan-card .btn { white-space: nowrap !important; font-size: 0.9375rem; }
.btn-current-plan {
    border: 1px solid #4ade80;
    color: white;
    background: #4ade80;
    border-radius: 10px;
    font-weight: 600;
    padding: 10px 16px;
}
.btn-choose-plan {
    background: #fff;
    color: #64748b;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-weight: 600;
    padding: 10px 16px;
}
.btn-choose-plan:hover { background: #e2e8f0; color: #334155; border-color: #cbd5e1; }
.btn-choose-disabled {
    background: #f8fafc;
    color: #94a3b8;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-weight: 600;
    padding: 10px 16px;
}
.btn-upgrade {
    background: linear-gradient(90deg, #6d28d9, #8b5cf6);
    color: white !important;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    padding: 10px 16px;
}
.btn-upgrade:hover { color: white !important; opacity: 0.95; }
.btn-upgrade.btn-disabled {
    background: #e2e8f0;
    color: #94a3b8 !important;
}
.billing-footer { margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #f1f5f9; }
</style>
@endpush
@endsection
