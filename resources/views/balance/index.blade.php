@extends('layouts.app')

@section('title', 'Add Funds')

@section('content')
<div class="container balance-page" style="max-width: 900px;">
    <div class="page-header mb-4">
        <h1 class="page-title">Add Funds</h1>
        <p class="page-subtitle">Top up your balance to pay for link generation and subscriptions.</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 mb-4" style="border-radius: 12px;">{{ session('success') }}</div>
    @endif
    @if (session('info'))
        <div class="alert alert-info border-0 mb-4" style="border-radius: 12px;">{{ session('info') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger border-0 mb-4" style="border-radius: 12px;">{{ session('error') }}</div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card balance-summary-card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3" style="color: #1e293b;">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="me-1 d-inline"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Top Up Summary
                    </h5>
                    <p class="text-muted small mb-2">Current balance</p>
                    <p class="h4 fw-bold mb-3" style="color: #1e293b;">${{ number_format($balance, 2) }} USD</p>
                    <label for="amount" class="form-label text-muted small">Amount to add (USD)</label>
                    <input type="number" step="0.01" min="0.10" max="10000" class="form-control form-control-lg mb-0" id="amount" value="10" required style="border-radius: 10px;">
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card payment-option-card border-0 shadow-sm" style="border-radius: 12px; height: 100%;">
                <div class="card-body p-4 d-flex flex-column">
                    <h5 class="fw-bold mb-2" style="color: #1e293b;">Pay with Tron (USDT / TRX)</h5>
                    <div class="payment-icon payment-icon-tron mb-3"><img src="{{ asset('images/tron-logo.png') }}" alt="Tron" width="32" height="32" class="payment-icon-img"></div>
                    <p class="small text-muted mb-3">Fast USDT or TRX payment on Tron network</p>
                    <div class="check-item mb-1"><span class="check-icon text-primary">✓</span> Secure and quick on-chain</div>
                    <div class="check-item text-muted mb-3"><span class="check-icon">✓</span> Low fees</div>
                    <div class="mt-auto">
                        @if(config('services.coinrush.store_key'))
                            <button type="button" class="btn btn-tron-balance w-100" id="topup-btn">Continue with Tron</button>
                        @else
                            <button type="button" class="btn btn-tron-balance w-100" disabled style="opacity: 0.6;">Continue with Tron</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card payment-option-card border-0 shadow-sm" style="border-radius: 12px; height: 100%;">
                <div class="card-body p-4 d-flex flex-column">
                    <h5 class="fw-bold mb-2" style="color: #1e293b;">Pay with Heleket (Crypto)</h5>
                    <div class="payment-icon payment-icon-heleket mb-3">H</div>
                    <p class="small text-muted mb-3">Pay via crypto checkout</p>
                    <div class="check-item mb-1"><span class="check-icon text-primary">✓</span> Bitcoin, Ethereum, and more</div>
                    <div class="check-item text-muted mb-3"><span class="check-icon">✓</span> Secure checkout</div>
                    <div class="mt-auto">
                        @if ($heleketAvailable ?? false)
                            <form method="POST" action="{{ route('balance.heleket.initiate') }}" id="heleket-form">
                                @csrf
                                <input type="hidden" name="amount" id="heleket-amount">
                                <button type="submit" class="btn btn-heleket-balance w-100">Continue with Heleket</button>
                            </form>
                        @else
                            <button type="button" class="btn btn-heleket-balance w-100" disabled style="opacity: 0.6;">Continue with Heleket</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="secure-footer-balance card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <span class="text-success">✓</span>
                <span class="fw-medium" style="color: #1e293b;">Secure payment via CoinRush (Tron) and Heleket</span>
            </div>
            <p class="small text-muted mb-0">You will return here after payment.</p>
            <div class="d-flex align-items-center gap-2">
                <span class="logo-box logo-cr">CR</span>
                <span class="logo-box logo-heleket">H</span>
            </div>
        </div>
    </div>

    <div class="card card-dashboard">
        <div class="card-body p-4">
            <h5 class="card-title fw-bold mb-3" style="color: #1e293b;">Recent transactions</h5>
            @forelse($transactions as $tx)
                <div class="d-flex justify-content-between align-items-center py-3 border-bottom" style="border-color: #f1f5f9 !important;">
                    <div>
                        <span class="fw-medium">${{ number_format($tx->amount, 2) }}</span>
                        <span class="text-muted small ms-1">— {{ $tx->provider_ref ?? 'Payment' }}</span>
                    </div>
                    <span class="badge rounded-pill {{ $tx->status === 'paid' ? 'bg-success' : ($tx->status === 'failed' ? 'bg-danger' : 'bg-secondary') }}">{{ ucfirst($tx->status) }}</span>
                </div>
            @empty
                <p class="text-muted mb-0 py-2">No transactions yet.</p>
            @endforelse
        </div>
    </div>
</div>

@push('styles')
<style>
.payment-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.25rem; }
.payment-icon-tron { background: linear-gradient(135deg, #ede9fe, #e9d5ff); color: #7c3aed; }
.payment-icon-tron .payment-icon-img { object-fit: contain; }
.payment-icon-heleket { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #dc2626; }
.check-item { display: flex; align-items: center; gap: 8px; font-size: 0.875rem; }
.check-item .check-icon { color: #6366f1; }
.check-item.text-muted .check-icon { color: #94a3b8; }
.btn-tron-balance { background: linear-gradient(90deg, #6d28d9, #8b5cf6); color: white !important; border: none; border-radius: 10px; font-weight: 600; padding: 10px 20px; }
.btn-tron-balance:hover { color: white !important; opacity: 0.95; }
.btn-heleket-balance { background: linear-gradient(90deg, #dc2626, #ef4444); color: white !important; border: none; border-radius: 10px; font-weight: 600; padding: 10px 20px; }
.btn-heleket-balance:hover { color: white !important; opacity: 0.95; }
.logo-box { width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.75rem; }
.logo-cr { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; }
.logo-heleket { background: linear-gradient(135deg, #dc2626, #ef4444); color: white; }
</style>
@endpush

<script>
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
