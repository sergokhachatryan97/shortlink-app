@extends('layouts.app')

@section('title', 'Add Funds')

@section('content')
<div class="cosmic-page-section">
    <div class="container cosmic-container" style="max-width: 900px;">
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

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="cosmic-card p-4 h-100">
                    <h5 class="cosmic-card-title mb-3">Top Up Summary</h5>
                    <p class="cosmic-text-muted small mb-2">Current balance</p>
                    <p class="cosmic-balance-amount mb-3">${{ number_format($balance, 2) }} USD</p>
                    <label for="amount" class="cosmic-label small">Amount to add (USD)</label>
                    <input type="number" step="0.01" min="0.10" max="10000" class="cosmic-input form-control form-control-lg mb-0" id="amount" value="10" required>
                </div>
            </div>

            <div class="col-md-4">
                <div class="cosmic-card p-4 h-100 d-flex flex-column">
                    <h5 class="cosmic-card-title mb-2">Pay with Tron (USDT / TRX)</h5>
                    <div class="cosmic-payment-icon cosmic-payment-tron mb-3">
                        <img src="{{ asset('images/tron-logo.png') }}" alt="Tron" width="32" height="32" class="payment-icon-img">
                    </div>
                    <p class="cosmic-text-muted small mb-3">Fast USDT or TRX payment on Tron network</p>
                    <div class="cosmic-check-item mb-1">✓ Secure and quick on-chain</div>
                    <div class="cosmic-check-item cosmic-text-muted mb-3">✓ Low fees</div>
                    <div class="mt-auto">
                        @if(config('services.coinrush.store_key'))
                            <button type="button" class="btn cosmic-btn-primary w-100" id="topup-btn">Continue with Tron</button>
                        @else
                            <button type="button" class="btn cosmic-btn-disabled w-100" disabled>Continue with Tron</button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="cosmic-card p-4 h-100 d-flex flex-column">
                    <h5 class="cosmic-card-title mb-2">Pay with Heleket (Crypto)</h5>
                    <div class="cosmic-payment-icon cosmic-payment-heleket mb-3">H</div>
                    <p class="cosmic-text-muted small mb-3">Pay via crypto checkout</p>
                    <div class="cosmic-check-item mb-1">✓ Bitcoin, Ethereum, and more</div>
                    <div class="cosmic-check-item cosmic-text-muted mb-3">✓ Secure checkout</div>
                    <div class="mt-auto">
                        @if ($heleketAvailable ?? false)
                            <form method="POST" action="{{ route('balance.heleket.initiate') }}" id="heleket-form">
                                @csrf
                                <input type="hidden" name="amount" id="heleket-amount">
                                <button type="submit" class="btn cosmic-btn-heleket w-100">Continue with Heleket</button>
                            </form>
                        @else
                            <button type="button" class="btn cosmic-btn-disabled w-100" disabled>Continue with Heleket</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="cosmic-card p-4 mb-4">
            <h5 class="cosmic-card-title d-flex align-items-center gap-2 mb-3">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                Contact Support
            </h5>
            <p class="cosmic-text-muted mb-2">For any questions or assistance, please contact us:</p>
            <p class="mb-2">
                <a href="mailto:{{ config('app.support_email') }}" class="cosmic-email">{{ config('app.support_email') }}</a>
            </p>
            @if(config('app.support_telegram'))
            <p class="mb-0">
                <a href="{{ config('app.support_telegram') }}" target="_blank" rel="noopener" class="cosmic-email">Telegram</a>
            </p>
            @endif
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
.cosmic-card {
    background: rgba(30, 30, 45, 0.7);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
}
.cosmic-card-title { color: #fff; font-weight: 600; }
.cosmic-text-muted { color: rgba(255,255,255,0.65); }
.cosmic-label { color: rgba(255,255,255,0.8); }
.cosmic-balance-amount { font-size: 1.5rem; font-weight: 700; color: #fff; }
.cosmic-input {
    background: rgba(30,30,45,0.8) !important;
    border: 1px solid rgba(255,255,255,0.15) !important;
    color: #fff !important;
    border-radius: 10px;
}
.cosmic-input:focus {
    background: rgba(30,30,45,0.9) !important;
    border-color: #a78bfa !important;
    box-shadow: 0 0 0 3px rgba(167,139,250,0.3);
    color: #fff !important;
}
.cosmic-payment-icon {
    width: 48px; height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.25rem;
}
.cosmic-payment-tron { background: rgba(167,139,250,0.2); }
.cosmic-payment-tron .payment-icon-img { object-fit: contain; }
.cosmic-payment-heleket { background: rgba(239,68,68,0.2); color: #fca5a5; }
.cosmic-check-item { color: rgba(255,255,255,0.85); font-size: 0.875rem; }
.cosmic-btn-primary {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border: none;
    color: #fff !important;
    font-weight: 600;
    padding: 10px 20px;
    border-radius: 10px;
}
.cosmic-btn-primary:hover { opacity: 0.95; color: #fff !important; }
.cosmic-btn-heleket {
    background: linear-gradient(135deg, #dc2626, #ef4444);
    border: none;
    color: #fff !important;
    font-weight: 600;
    padding: 10px 20px;
    border-radius: 10px;
}
.cosmic-btn-heleket:hover { opacity: 0.95; color: #fff !important; }
.cosmic-btn-disabled {
    background: rgba(30,30,45,0.5);
    border: 1px solid rgba(255,255,255,0.1);
    color: rgba(255,255,255,0.5);
    border-radius: 10px;
}
.cosmic-email { color: #a78bfa; font-weight: 600; text-decoration: none; font-size: 1.1rem; }
.cosmic-email:hover { color: #c4b5fd; }
.cosmic-alert { border-radius: 12px; padding: 1rem 1.25rem; }
.cosmic-alert-success { background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.4); color: #86efac; }
.cosmic-alert-info { background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.4); color: #93c5fd; }
.cosmic-alert-danger { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.4); color: #fca5a5; }
.cosmic-tx-row { border-bottom: 1px solid rgba(255,255,255,0.08); }
.cosmic-tx-row:last-child { border-bottom: none; }
.cosmic-tx-amount { color: #fff; font-weight: 500; }
.cosmic-badge-status {
    font-size: 0.75rem;
    padding: 4px 10px;
    border-radius: 20px;
}
.cosmic-badge-paid { background: rgba(34,197,94,0.3); color: #86efac; }
.cosmic-badge-failed { background: rgba(239,68,68,0.3); color: #fca5a5; }
.cosmic-badge-pending { background: rgba(255,255,255,0.2); color: rgba(255,255,255,0.8); }
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
