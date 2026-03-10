@extends('layouts.app')

@section('title', 'Add Funds')

@section('content')
<div class="cosmic-page-section">
    <div class="container cosmic-container" style="max-width: 720px;">
        <div class="cosmic-page-header mb-4">
            <h1 class="cosmic-page-title">Add Funds</h1>
            <p class="cosmic-page-subtitle mb-0">Top up your balance to pay for link generation and subscriptions.</p>
        </div>

        @if (session('success'))
            <div class="cosmic-alert cosmic-alert-success mb-4">{{ session('success') }}</div>
        @endif
        @if (session('info'))
            <div class="cosmic-alert cosmic-alert-info mb-4">{{ session('info') }}</div>
        @endif
        @if (session('error'))
            <div class="cosmic-alert cosmic-alert-danger mb-4">{{ session('error') }}</div>
        @endif

        <div class="cosmic-addfunds-card p-4 mb-4" id="addfunds-form">
            <div class="addfunds-balance-box mb-4">
                <div class="cosmic-text-muted small mb-1">Current Balance</div>
                <div class="d-flex align-items-center gap-2">
                    <div class="addfunds-wallet-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </div>
                    <span class="addfunds-balance-amount">${{ number_format($balance, 2) }} USD</span>
                </div>
            </div>

            <div class="mb-4">
                <label for="amount" class="cosmic-label mb-2">Amount to add</label>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <input type="number" step="0.01" min="0.10" max="10000" class="cosmic-input form-control" id="amount" value="{{ $prefillAmount }}" required style="max-width: 120px;">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn addfunds-quick-btn" data-amount="25">$25</button>
                        <button type="button" class="btn addfunds-quick-btn" data-amount="50">$50</button>
                        <button type="button" class="btn addfunds-quick-btn" data-amount="100">$100</button>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="cosmic-label mb-2">Choose Payment Method</label>
                <div class="addfunds-methods d-flex gap-3 flex-wrap">
                    <div class="addfunds-method addfunds-method-tron active" data-method="tron">
                        <span class="addfunds-method-badge">★ Recommended</span>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="addfunds-method-icon addfunds-icon-tron">
                                <img src="{{ asset('images/tron-logo.png') }}" alt="Tron" width="24" height="24">
                            </div>
                            <span class="cosmic-card-title mb-0">Pay with Tron</span>
                        </div>
                        <p class="cosmic-text-muted small mb-2">USDT / TRX</p>
                        <div class="addfunds-check">✓ Low fees</div>
                        <div class="addfunds-check">✓ Fast payment</div>
                    </div>
                    <div class="addfunds-method addfunds-method-heleket" data-method="heleket">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="addfunds-method-icon addfunds-icon-heleket">H</div>
                            <span class="cosmic-card-title mb-0">Pay with Heleket</span>
                        </div>
                        <p class="cosmic-text-muted small mb-2">Bitcoin, Ethereum, and more</p>
                        <div class="addfunds-check">✓ Secure checkout</div>
                    </div>
                </div>
            </div>

            <div class="addfunds-summary mb-4">
                <div class="d-flex justify-content-between mb-1"><span class="cosmic-text-muted">You will pay:</span> <span id="summary-pay">$0.00</span></div>
                <div class="d-flex justify-content-between mb-1"><span class="cosmic-text-muted">Network fee:</span> <span>~$0.10</span></div>
                <div class="d-flex justify-content-between addfunds-total"><span>Total:</span> <span id="summary-total">$0.10</span></div>
            </div>

            <div class="addfunds-secure mb-4">✓ Secure crypto payment confirmed on the blockchain</div>

            <div class="d-flex justify-content-end gap-2">
                @if(config('services.coinrush.store_key'))
                <button type="button" class="btn cosmic-btn-primary addfunds-pay-btn active" id="topup-btn" data-method="tron">Pay with Tron</button>
                @else
                <button type="button" class="btn cosmic-btn-disabled" disabled>Pay with Tron</button>
                @endif
                @if ($heleketAvailable ?? false)
                <form method="POST" action="{{ route('balance.heleket.initiate') }}" id="heleket-form" class="d-inline">
                    @csrf
                    <input type="hidden" name="amount" id="heleket-amount">
                    <button type="submit" class="btn cosmic-btn-heleket addfunds-pay-btn" id="heleket-btn" data-method="heleket">Pay with Heleket</button>
                </form>
                @endif
            </div>
        </div>

        <div class="cosmic-card p-4 mb-4">
            <h5 class="cosmic-card-title mb-3">Subscription Plans</h5>
            <p class="cosmic-text-muted small mb-4">Use your balance to buy a plan. Add funds above, then choose a plan.</p>
            <div class="row g-4">
                @foreach($plans ?? [] as $plan)
                @php
                    $hasActivePlan = (bool)($activeSubscription ?? null);
                    $isCurrentPlan = $hasActivePlan && $activeSubscription->plan->id === $plan->id;
                    $canUpgrade = $hasActivePlan && $plan->sort_order > $activeSubscription->plan->sort_order;
                    $upgradePriceDiff = 0;
                    if ($canUpgrade) {
                        $currentPlan = $activeSubscription->plan;
                        $daysRemaining = max(0, now()->diffInDays($activeSubscription->ends_at, false));
                        $currentDuration = max(1, (int)$currentPlan->duration_days);
                        $fullDiff = (float)$plan->price_usd - (float)$currentPlan->price_usd;
                        $upgradePriceDiff = round($fullDiff * ($daysRemaining / $currentDuration), 2);
                    }
                    $canAffordUpgrade = $canUpgrade && ($balance ?? 0) >= $upgradePriceDiff;
                    $canBuyWithBalance = !$hasActivePlan && ($balance ?? 0) >= (float)$plan->price_usd;
                    $isRecommended = strtolower($plan->slug ?? '') === 'pro';
                    $planAmount = (float)$plan->price_usd;
                    $addFundsAmount = max(0.10, round($planAmount - ($balance ?? 0), 2));
                    $iconClass = match(strtolower($plan->slug ?? '')) {
                        'starter' => 'icon-lightning',
                        'vip' => 'icon-star',
                        default => 'icon-check',
                    };
                @endphp
                <div class="col-md-4 d-flex">
                    <div class="cosmic-plan-card-balance w-100 d-flex flex-column {{ $isCurrentPlan ? 'cosmic-plan-current' : '' }} {{ $isRecommended ? 'cosmic-plan-recommended' : '' }}">
                        @if ($isRecommended)
                        <div class="cosmic-plan-badge">★ Recommended</div>
                        @endif
                        <div class="cosmic-plan-body p-4 d-flex flex-column flex-grow-1">
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
                            <p class="cosmic-plan-desc mb-2">
                                @if ($plan->isUnlimited())
                                    <strong>Unlimited links</strong> until subscription ends
                                @else
                                    {{ $plan->description }}
                                @endif
                            </p>
                            <div class="cosmic-plan-features mb-3">
                                <span class="cosmic-plan-feature">{{ $plan->links_limit ? number_format($plan->links_limit) . ' links' : 'Unlimited links' }}</span>
                                <span class="cosmic-plan-feature">{{ (int)$plan->duration_days }} days</span>
                            </div>
                            <p class="cosmic-plan-price mb-3">${{ number_format($plan->price_usd, 2) }}{{ strtolower($plan->slug ?? '') === 'vip' ? '/yr' : '/mo' }}</p>

                            <div class="mt-auto pt-2">
                            @if ($isCurrentPlan)
                                <button type="button" class="btn cosmic-btn-active w-100" disabled>Active</button>
                            @elseif ($canUpgrade)
                                @if ($canAffordUpgrade)
                                    <form method="POST" action="{{ route('subscription.upgrade') }}">
                                        @csrf
                                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                        <button type="submit" class="btn w-100 cosmic-btn-primary">Upgrade to {{ $plan->name }}</button>
                                        @if ($upgradePriceDiff > 0)
                                        <p class="cosmic-pay-today small mt-2 mb-0 text-center">Pay ${{ number_format($upgradePriceDiff, 2) }} today</p>
                                        @endif
                                    </form>
                                @else
                                    <a href="{{ route('balance.index', ['amount' => $addFundsAmount]) }}" class="btn cosmic-btn-add-funds w-100 addfunds-plan-link" data-amount="{{ $addFundsAmount }}">Add funds</a>
                                @endif
                            @elseif ($hasActivePlan)
                                <button type="button" class="btn cosmic-btn-disabled w-100" disabled>Downgrade not available</button>
                            @else
                                @if ($canBuyWithBalance)
                                    <form method="POST" action="{{ route('subscription.purchase') }}">
                                        @csrf
                                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                        <button type="submit" class="btn w-100 {{ $isRecommended ? 'cosmic-btn-primary' : 'cosmic-btn-plan' }}">Buy {{ $plan->name }}</button>
                                    </form>
                                @else
                                    <a href="{{ route('balance.index', ['amount' => $addFundsAmount]) }}" class="btn cosmic-btn-add-funds w-100 addfunds-plan-link" data-amount="{{ $addFundsAmount }}">Add funds</a>
                                @endif
                            @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="cosmic-card p-4">
            <h5 class="cosmic-card-title mb-3">Recent transactions</h5>
            @forelse($transactions as $tx)
                <div class="cosmic-tx-row d-flex justify-content-between align-items-center py-3">
                    <div>
                        <span class="cosmic-tx-amount">${{ number_format($tx->amount, 2) }}</span>
                        <span class="cosmic-text-muted small ms-1">— {{ $tx->provider_ref ?? 'Payment' }}</span>
                    </div>
                    <span class="cosmic-badge-status cosmic-badge-{{ $tx->status }}">{{ ucfirst($tx->status) }}</span>
                </div>
            @empty
                <p class="cosmic-text-muted mb-0 py-2">No transactions yet.</p>
            @endforelse
        </div>
    </div>
</div>

@push('styles')
<style>
.cosmic-page-section { min-height: calc(100vh - var(--navbar-height, 64px) - 80px); background: #0a0a12 url('{{ asset('images/hero-bg.png') }}') no-repeat center center; background-size: cover; margin: -1.5rem 0 0; padding: 2rem 1rem 3rem; position: relative; }
.cosmic-page-section::before { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(10,10,18,0.75) 0%, rgba(10,10,18,0.9) 100%); pointer-events: none; }
.cosmic-container { position: relative; z-index: 1; }
.cosmic-page-title { font-size: 1.75rem; font-weight: 700; color: #fff; }
.cosmic-page-subtitle { color: rgba(255,255,255,0.7); font-size: 0.9375rem; }
.cosmic-text-muted { color: rgba(255,255,255,0.65); }
.cosmic-card { background: rgba(30, 30, 45, 0.7); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.3); }
.cosmic-card-title { color: #fff; font-weight: 600; }
.cosmic-label { color: rgba(255,255,255,0.8); }
.cosmic-input { background: rgba(30,30,45,0.8) !important; border: 1px solid rgba(167,139,250,0.3) !important; color: #fff !important; border-radius: 10px; }
.cosmic-input:focus { background: rgba(30,30,45,0.9) !important; border-color: #a78bfa !important; box-shadow: 0 0 0 3px rgba(167,139,250,0.3); color: #fff !important; }
.cosmic-email { color: #a78bfa; font-weight: 600; text-decoration: none; }
.cosmic-email:hover { color: #c4b5fd; }
.cosmic-btn-primary { background: linear-gradient(135deg, #6366f1, #8b5cf6); border: none; color: #fff !important; font-weight: 600; padding: 10px 24px; border-radius: 10px; }
.cosmic-btn-primary:hover { opacity: 0.95; color: #fff !important; }
.cosmic-btn-heleket { background: linear-gradient(135deg, #dc2626, #ef4444); border: none; color: #fff !important; font-weight: 600; padding: 10px 24px; border-radius: 10px; }
.cosmic-btn-heleket:hover { opacity: 0.95; color: #fff !important; }
.cosmic-btn-plan { background: rgba(30,30,45,0.9); border: 1px solid rgba(255,255,255,0.2); color: #fff; border-radius: 8px; font-weight: 600; }
.cosmic-btn-active { background: rgba(34,197,94,0.25); border: 1px solid rgba(34,197,94,0.5); color: #86efac; border-radius: 8px; font-weight: 600; cursor: default; }
.cosmic-btn-add-funds { background: transparent; border: 1px solid rgba(167,139,250,0.5); color: #a78bfa; border-radius: 8px; font-weight: 600; text-decoration: none; display: block; text-align: center; padding: 6px 12px; font-size: 0.875rem; }
.cosmic-btn-add-funds:hover { background: rgba(167,139,250,0.2); color: #c4b5fd; }
.cosmic-btn-disabled { background: rgba(30,30,45,0.5); color: rgba(255,255,255,0.5); border-radius: 10px; padding: 10px 24px; }
.cosmic-alert { border-radius: 12px; padding: 1rem 1.25rem; }
.cosmic-alert-success { background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.4); color: #86efac; }
.cosmic-alert-info { background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.4); color: #93c5fd; }
.cosmic-alert-danger { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.4); color: #fca5a5; }
.cosmic-tx-row { border-bottom: 1px solid rgba(255,255,255,0.08); }
.cosmic-tx-row:last-child { border-bottom: none; }
.cosmic-tx-amount { color: #fff; font-weight: 500; }
.cosmic-badge-status { font-size: 0.75rem; padding: 4px 10px; border-radius: 20px; }
.cosmic-badge-paid { background: rgba(34,197,94,0.3); color: #86efac; }
.cosmic-badge-failed { background: rgba(239,68,68,0.3); color: #fca5a5; }
.cosmic-badge-pending { background: rgba(255,255,255,0.2); color: rgba(255,255,255,0.8); }
.cosmic-link { color: #a78bfa; text-decoration: none; }
.cosmic-link:hover { color: #c4b5fd; }
.cosmic-addfunds-card { background: rgba(30, 30, 45, 0.7); border: 1px solid rgba(167,139,250,0.2); border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.3), 0 0 0 1px rgba(167,139,250,0.1); }
.addfunds-balance-box { padding: 1rem 1.25rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(167,139,250,0.2); border-radius: 10px; }
.addfunds-wallet-icon { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; color: #a78bfa; }
.addfunds-balance-amount { font-size: 1.5rem; font-weight: 700; color: #fff; }
.addfunds-quick-btn { background: rgba(30,30,45,0.8); border: 1px solid rgba(167,139,250,0.3); color: #fff; border-radius: 8px; font-weight: 600; padding: 6px 14px; }
.addfunds-quick-btn:hover { background: rgba(40,40,60,0.9); color: #fff; border-color: rgba(167,139,250,0.5); }
.addfunds-methods { display: flex; gap: 1rem; }
.addfunds-method { flex: 1; min-width: 180px; padding: 1rem; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1); cursor: pointer; position: relative; transition: all 0.2s; }
.addfunds-method:hover { border-color: rgba(167,139,250,0.3); }
.addfunds-method.active { border-color: rgba(167,139,250,0.6); box-shadow: 0 0 0 2px rgba(167,139,250,0.2); }
.addfunds-method-badge { position: absolute; top: 8px; right: 8px; font-size: 0.7rem; color: #f59e0b; }
.addfunds-method-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; }
.addfunds-icon-tron { background: rgba(167,139,250,0.2); }
.addfunds-icon-heleket { background: rgba(239,68,68,0.2); color: #fca5a5; }
.addfunds-check { font-size: 0.875rem; color: rgba(255,255,255,0.85); }
.addfunds-summary { padding: 1rem; background: rgba(0,0,0,0.15); border-radius: 10px; }
.addfunds-total { font-weight: 700; font-size: 1.1rem; margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid rgba(255,255,255,0.1); }
.addfunds-secure { font-size: 0.875rem; color: rgba(255,255,255,0.7); }
.cosmic-plan-card-balance { background: rgba(30, 30, 45, 0.7); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; position: relative; overflow: visible; }
.cosmic-plan-card-balance.cosmic-plan-recommended { border-color: rgba(167,139,250,0.5); box-shadow: 0 0 20px rgba(167,139,250,0.2); }
.cosmic-plan-card-balance.cosmic-plan-current { border-color: rgba(34,197,94,0.5); }
.cosmic-plan-card-balance .cosmic-plan-badge { position: absolute; top: -8px; right: 16px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; font-size: 0.75rem; font-weight: 600; padding: 6px 12px; border-radius: 20px; }
.cosmic-plan-card-balance .cosmic-plan-body { display: flex; flex-direction: column; }
.cosmic-plan-card-balance .cosmic-plan-icon { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0; }
.cosmic-plan-card-balance .cosmic-plan-icon.icon-lightning,
.cosmic-plan-card-balance .cosmic-plan-icon.icon-check { background: rgba(255,255,255,0.2); }
.cosmic-plan-card-balance.cosmic-plan-recommended .cosmic-plan-icon { background: rgba(167,139,250,0.4); }
.cosmic-plan-card-balance .cosmic-plan-icon.icon-star { background: rgba(255,255,255,0.2); }
.cosmic-plan-card-balance .cosmic-plan-name { color: #fff; font-weight: 600; }
.cosmic-plan-card-balance .cosmic-plan-desc { color: rgba(255,255,255,0.8); font-size: 0.9375rem; line-height: 1.5; }
.cosmic-plan-features { display: flex; flex-wrap: wrap; gap: 0.5rem; font-size: 0.8125rem; }
.cosmic-plan-feature { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.85); padding: 4px 10px; border-radius: 6px; }
.cosmic-plan-card-balance .cosmic-plan-price { font-size: 1.25rem; font-weight: 700; color: #fff; }
.cosmic-plan-card-balance.cosmic-plan-recommended .cosmic-plan-price { font-size: 1.5rem; color: #a78bfa; }
.cosmic-plan-card-balance .cosmic-btn-disabled { background: rgba(30,30,45,0.5); border: 1px solid rgba(255,255,255,0.1); color: rgba(255,255,255,0.5); border-radius: 10px; font-weight: 600; padding: 10px 16px; }
.cosmic-plan-card-balance .cosmic-pay-today { color: rgba(255,255,255,0.6); }
.addfunds-pay-btn { display: none !important; }
.addfunds-pay-btn.active { display: inline-flex !important; }
</style>
@endpush

<script>
(function() {
    const amountInput = document.getElementById('amount');
    const summaryPay = document.getElementById('summary-pay');
    const summaryTotal = document.getElementById('summary-total');
    const networkFee = 0.10;
    function updateSummary() {
        const amt = parseFloat(amountInput.value) || 0;
        summaryPay.textContent = '$' + amt.toFixed(2);
        summaryTotal.textContent = '$' + (amt + networkFee).toFixed(2);
    }
    amountInput.addEventListener('input', updateSummary);
    document.querySelectorAll('.addfunds-quick-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            amountInput.value = this.dataset.amount;
            updateSummary();
        });
    });
    document.querySelectorAll('.addfunds-plan-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            var amt = parseFloat(this.dataset.amount) || 0;
            if (amt > 0) {
                e.preventDefault();
                amountInput.value = amt;
                updateSummary();
                document.getElementById('addfunds-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
    updateSummary();

    const methods = document.querySelectorAll('.addfunds-method');
    const payBtns = document.querySelectorAll('.addfunds-pay-btn');
    function setActiveMethod(method) {
        methods.forEach(x => x.classList.remove('active'));
        document.querySelector('.addfunds-method[data-method="' + method + '"]')?.classList.add('active');
        payBtns.forEach(function(b) {
            b.classList.toggle('active', b.dataset.method === method);
        });
    }
    methods.forEach(function(m) {
        m.addEventListener('click', function() {
            setActiveMethod(this.dataset.method);
        });
    });
    var defaultMethod = document.getElementById('topup-btn') && !document.getElementById('topup-btn').disabled ? 'tron' : 'heleket';
    setActiveMethod(defaultMethod);
})();
document.getElementById('heleket-form')?.addEventListener('submit', function() {
    document.getElementById('heleket-amount').value = document.getElementById('amount').value;
});
</script>
@if(config('services.coinrush.store_key'))
<script src="{{ asset('js/tron-payment.js') }}"></script>
<script>
document.getElementById('topup-btn')?.addEventListener('click', async function() {
    const btn = this;
    const amount = parseFloat(document.getElementById('amount').value);
    if (isNaN(amount) || amount < 0.10 || amount > 10000) {
        alert('Please enter amount between $0.10 and $10,000');
        return;
    }
    const prepareUrl = '{{ route("balance.topup.prepare") }}';
    const successUrl = '{{ route("balance.tron.success") }}';
    const storeKey = @json(config('services.coinrush.store_key'));
    const apiUrl = @json(config('services.coinrush.api_url', 'https://coinrush.link/store'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value;
    btn.disabled = true;
    let orderId;
    try {
        const res = await fetch(prepareUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ amount }),
            credentials: 'same-origin'
        });
        const data = await res.json();
        if (!res.ok || !data.order_id) throw new Error(data.error || 'Failed');
        orderId = data.order_id;
    } catch (err) {
        btn.disabled = false;
        alert(err.message);
        return;
    }
    if (!window.TronPayment) { alert('Widget not loaded'); btn.disabled = false; return; }
    TronPayment.init({ storeKey, apiUrl });
    TronPayment.openPayment({
        transactionId: orderId,
        amount,
        asset: 'USDT',
        onSuccess: () => { window.location.href = successUrl + '?order_id=' + orderId; },
        onError: (e) => { alert(e?.message); btn.disabled = false; },
        onCancel: () => { btn.disabled = false; }
    });
});
</script>
@endif
@endsection
