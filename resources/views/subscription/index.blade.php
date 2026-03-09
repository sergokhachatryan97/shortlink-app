@extends('layouts.app')

@section('title', 'Subscription Plans')

@section('content')
<div class="container" style="max-width: 720px;">
    <h1 class="mb-2 fw-bold" style="font-size: 1.75rem; color: #1e293b;">Subscription Plans</h1>
    <p class="text-muted mb-4">Unlock more links and store them until your plan ends.</p>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm mb-4" style="border-radius: 12px;">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-4" style="border-radius: 12px;">{{ session('error') }}</div>
    @endif

    @if ($activeSubscription)
        <div class="alert alert-success border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <strong>Active plan:</strong> {{ $activeSubscription->plan->name }} until {{ $activeSubscription->ends_at->format('M j, Y') }}
            @if ($activeSubscription->provider_ref === 'balance')
                <span class="badge bg-light text-dark ms-2">Paid with balance</span>
            @endif
        </div>
    @elseif ($lastExpiredSubscription)
        <div class="alert alert-warning border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <strong>Subscription expired.</strong> Your plan ({{ $lastExpiredSubscription->plan->name }}) ended on {{ $lastExpiredSubscription->ends_at->format('M j, Y') }}. Purchase a new plan with balance or payment to continue.
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body py-3 px-4">
            <span class="text-muted small">Your balance:</span>
            <strong class="ms-2">${{ number_format($balance ?? 0, 2) }} USD</strong>
            <a href="{{ route('balance.index') }}" class="btn btn-sm btn-outline-primary ms-2">Top up</a>
        </div>
    </div>

    <div class="row g-4">
        @foreach($plans as $plan)
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body p-4 d-flex flex-column">
                    <h5 class="card-title fw-bold mb-2" style="color: #1e293b;">{{ $plan->name }}</h5>
                    <p class="text-muted small mb-3">{{ $plan->description }}</p>
                    <p class="h4 mb-3 fw-bold" style="color: var(--brand);">${{ number_format($plan->price_usd, 2) }}/mo</p>
                    <p class="small text-muted mb-3">
                        {{ $plan->isUnlimited() ? 'Unlimited' : number_format($plan->links_limit) }} links
                        · Links stored until subscription ends
                    </p>
                    @php
                        $hasActivePlan = (bool) $activeSubscription;
                        $isCurrentPlan = $hasActivePlan && $activeSubscription->plan->id === $plan->id;
                        $canUpgrade = $hasActivePlan && $plan->sort_order > $activeSubscription->plan->sort_order;
                        $upgradePriceDiff = $canUpgrade ? (float) $plan->price_usd - (float) $activeSubscription->plan->price_usd : 0;
                        $canAffordUpgrade = $canUpgrade && ($balance ?? 0) >= $upgradePriceDiff;
                        $canBuyWithBalance = !$hasActivePlan && ($balance ?? 0) >= (float) $plan->price_usd;
                    @endphp
                    @if ($isCurrentPlan)
                        <div class="mt-auto">
                            <button type="button" class="btn w-100 btn-outline-success" disabled>Current plan</button>
                        </div>
                    @elseif ($canUpgrade)
                        <form method="POST" action="{{ route('subscription.upgrade') }}" class="mt-auto">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            <button type="submit" class="btn w-100 {{ $canAffordUpgrade ? 'btn-primary' : 'btn-outline-secondary' }}" style="{{ $canAffordUpgrade ? 'background: linear-gradient(135deg, var(--brand), var(--accent)); border: none; font-weight: 600;' : '' }}" {{ !$canAffordUpgrade ? 'disabled' : '' }}>
                                {{ $canAffordUpgrade ? 'Upgrade ($' . number_format($upgradePriceDiff, 2) . ')' : 'Insufficient balance' }}
                            </button>
                        </form>
                    @elseif ($hasActivePlan)
                        <div class="mt-auto">
                            <button type="button" class="btn w-100 btn-outline-secondary" disabled>Downgrade not available</button>
                        </div>
                    @else
                        <form method="POST" action="{{ route('subscription.purchase') }}" class="mt-auto">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            <button type="submit" class="btn w-100 {{ $canBuyWithBalance ? 'btn-primary' : 'btn-outline-secondary' }}" style="{{ $canBuyWithBalance ? 'background: linear-gradient(135deg, var(--brand), var(--accent)); border: none; font-weight: 600;' : '' }}" {{ !$canBuyWithBalance ? 'disabled' : '' }}>
                                {{ $canBuyWithBalance ? 'Buy with balance' : 'Insufficient balance' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <p class="text-muted mt-4 small">
        When your subscription ends, generated links will be removed. Download your CSV before expiry.
    </p>
</div>
@endsection
