@extends('layouts.app')

@section('title', 'Complete Payment — Trastly')

@push('head')
<meta name="heleket" content="9701089f" />
@endpush

@section('content')
<div class="cosmic-page-section">
    <div class="container cosmic-container" style="max-width: 960px;">
        <a href="{{ route('shortlink.index') }}" class="cosmic-back-link d-inline-flex align-items-center gap-2 mb-4 text-decoration-none">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            {{ __('messages.nav.generator') }}
        </a>

        <div class="cosmic-page-header mb-4">
            <h1 class="cosmic-page-title">Complete Payment</h1>
            <p class="cosmic-page-subtitle mb-0">
                @if($freeTrialExhausted ?? false)
                    Your free trial has ended. Pay to generate {{ $count }} short links.
                @else
                    Pay to generate {{ $count }} short links ({{ $count - ($remaining ?? 0) }} over your {{ $remaining ?? 0 }} free).
                @endif
            </p>
        </div>

        @if (session('error'))
            <div class="cosmic-alert cosmic-alert-danger mb-4">{{ session('error') }}</div>
        @endif

        @php($tronAvailable = !empty($coinrushStoreKey ?? null))
        @if(!$tronAvailable && !($heleketAvailable ?? false))
            <div class="cosmic-alert cosmic-alert-info mb-4">
                Configure <code class="cosmic-code">COINRUSH_STORE_KEY</code> or <code class="cosmic-code">HELEKET_MERCHANT</code> / <code class="cosmic-code">HELEKET_PAYMENT_KEY</code> in <code class="cosmic-code">.env</code> to enable payments.
            </div>
        @endif

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="cosmic-pay-card cosmic-pay-summary h-100 p-4">
                    <h5 class="cosmic-card-title mb-3 d-flex align-items-center gap-2">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Payment Summary
                    </h5>
                    <p class="cosmic-pay-label mb-1">URL</p>
                    <p class="cosmic-pay-value text-break mb-3">{{ Str::limit($url ?? '', 48) }}</p>
                    <p class="cosmic-pay-label mb-1">Quantity</p>
                    <p class="cosmic-pay-value mb-3">{{ $count }} links</p>
                    <p class="cosmic-pay-label mb-1">Price per link</p>
                    <p class="cosmic-pay-value mb-3">${{ number_format($pricePerLink ?? 0.001, 3) }} USD</p>
                    <p class="cosmic-pay-total mb-0">${{ number_format($amount, 3) }}</p>
                    <p class="cosmic-text-muted small mt-3 mb-0">After payment, your links will be generated automatically.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="cosmic-pay-card cosmic-pay-option h-100 p-4 d-flex flex-column">
                    <h5 class="cosmic-card-title mb-2">Pay with Tron (USDT / TRX)</h5>
                    <div class="cosmic-pay-icon cosmic-pay-icon-tron mb-3">
                        <img src="{{ asset('images/tron-logo.png') }}" alt="Tron" width="32" height="32">
                    </div>
                    <p class="cosmic-text-muted small mb-3">Fast USDT or TRX payment on Tron network</p>
                    <div class="cosmic-check-row"><span class="cosmic-check-mark">✓</span> Secure and quick on-chain payment</div>
                    <div class="cosmic-check-row is-muted"><span class="cosmic-check-mark">✓</span> Low fees</div>
                    <div class="mt-auto pt-3">
                        @if($tronAvailable)
                            <button type="button" id="btn-tron" class="btn cosmic-btn-primary w-100">Continue with Tron</button>
                        @else
                            <button type="button" class="btn cosmic-btn-disabled w-100" disabled>Continue with Tron</button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="cosmic-pay-card cosmic-pay-option h-100 p-4 d-flex flex-column">
                    <h5 class="cosmic-card-title mb-2">Pay with Heleket (Crypto)</h5>
                    <div class="cosmic-pay-icon cosmic-pay-icon-heleket mb-3">H</div>
                    <p class="cosmic-text-muted small mb-3">Pay via crypto checkout</p>
                    <div class="cosmic-check-row"><span class="cosmic-check-mark">✓</span> Supports Bitcoin, Ethereum, and more</div>
                    <div class="cosmic-check-row is-muted"><span class="cosmic-check-mark">✓</span> Secure checkout</div>
                    <div class="mt-auto pt-3">
                        @if($heleketAvailable ?? false)
                            <form method="POST" action="{{ route('shortlink.payment.initiate') }}">
                                @csrf
                                <button type="submit" class="btn cosmic-btn-heleket w-100">Continue with Heleket</button>
                            </form>
                        @else
                            <button type="button" class="btn cosmic-btn-disabled w-100" disabled>Continue with Heleket</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="cosmic-pay-footer cosmic-card p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <span class="cosmic-secure-check">✓</span>
                <span class="cosmic-card-title mb-0 fw-normal" style="font-size:0.9375rem;">Secure payment via CoinRush (Tron) and Heleket</span>
            </div>
            <p class="cosmic-text-muted small mb-0">You will return to Trastly after payment.</p>
            <div class="d-flex align-items-center gap-2 ms-md-auto">
                <span class="cosmic-logo-pill cosmic-logo-cr">CR</span>
                <span class="cosmic-logo-pill cosmic-logo-heleket">H</span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.cosmic-page-section { min-height: calc(100vh - var(--navbar-height, 64px) - 80px); background: #0a0a12 url('{{ asset('images/hero-bg.png') }}') no-repeat center center; background-size: cover; margin: -1.5rem 0 0; padding: 2rem 1rem 3rem; position: relative; }
.cosmic-page-section::before { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(10,10,18,0.75) 0%, rgba(10,10,18,0.9) 100%); pointer-events: none; }
.cosmic-container { position: relative; z-index: 1; }
.cosmic-page-title { font-size: 1.75rem; font-weight: 700; color: #fff; }
.cosmic-page-subtitle { color: rgba(255,255,255,0.7); font-size: 0.9375rem; }
.cosmic-page-header { margin-bottom: 1.5rem; }
.cosmic-text-muted { color: rgba(255,255,255,0.65); }
.cosmic-card { background: rgba(30, 30, 45, 0.7); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.3); }
.cosmic-card-title { color: #fff; font-weight: 600; }
.cosmic-back-link { color: #a78bfa; font-weight: 500; font-size: 0.9375rem; }
.cosmic-back-link:hover { color: #c4b5fd; }
.cosmic-alert { border-radius: 12px; padding: 1rem 1.25rem; }
.cosmic-alert-danger { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.4); color: #fca5a5; }
.cosmic-alert-info { background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.4); color: #93c5fd; }
.cosmic-code { color: #e9d5ff; background: rgba(0,0,0,0.25); padding: 2px 6px; border-radius: 6px; font-size: 0.85em; }
.cosmic-pay-card {
    background: rgba(30, 30, 45, 0.72);
    border: 1px solid rgba(167,139,250,0.15);
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.35);
    transition: border-color 0.2s, box-shadow 0.2s;
}
.cosmic-pay-summary { border-color: rgba(167,139,250,0.25); }
.cosmic-pay-option:hover { border-color: rgba(167,139,250,0.35); box-shadow: 0 8px 36px rgba(99,102,241,0.12); }
.cosmic-pay-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em; color: rgba(255,255,255,0.5); margin-bottom: 0.25rem; }
.cosmic-pay-value { color: rgba(255,255,255,0.92); font-size: 0.9375rem; margin-bottom: 0; }
.cosmic-pay-total { font-size: 1.5rem; font-weight: 700; background: linear-gradient(135deg, #a78bfa, #818cf8); -webkit-background-clip: text; background-clip: text; color: transparent; }
.cosmic-pay-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.cosmic-pay-icon-tron { background: rgba(167,139,250,0.2); }
.cosmic-pay-icon-heleket { background: rgba(239,68,68,0.2); color: #fca5a5; font-weight: 700; font-size: 1.25rem; }
.cosmic-check-row { display: flex; align-items: center; gap: 8px; font-size: 0.875rem; color: rgba(255,255,255,0.88); margin-bottom: 6px; }
.cosmic-check-row.is-muted { color: rgba(255,255,255,0.55); }
.cosmic-check-mark { color: #a78bfa; flex-shrink: 0; }
.cosmic-check-row.is-muted .cosmic-check-mark { color: rgba(255,255,255,0.35); }
.cosmic-btn-primary { background: linear-gradient(135deg, #6366f1, #8b5cf6); border: none; color: #fff !important; font-weight: 600; padding: 10px 24px; border-radius: 10px; }
.cosmic-btn-primary:hover { opacity: 0.95; color: #fff !important; }
.cosmic-btn-heleket { background: linear-gradient(135deg, #dc2626, #ef4444); border: none; color: #fff !important; font-weight: 600; padding: 10px 24px; border-radius: 10px; }
.cosmic-btn-heleket:hover { opacity: 0.95; color: #fff !important; }
.cosmic-btn-disabled { background: rgba(30,30,45,0.5); color: rgba(255,255,255,0.45) !important; border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 10px 24px; cursor: not-allowed; }
.cosmic-pay-footer { border-color: rgba(167,139,250,0.2); }
.cosmic-secure-check { color: #4ade80; }
.cosmic-logo-pill { width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.75rem; }
.cosmic-logo-cr { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; }
.cosmic-logo-heleket { background: linear-gradient(135deg, #dc2626, #ef4444); color: #fff; }
</style>
@endpush

@push('scripts')
@if($tronAvailable)
<script src="{{ asset('js/tron-payment.js') }}"></script>
<script>
document.getElementById('btn-tron')?.addEventListener('click', async function() {
    const btn = this;
    const storeKey = @json($coinrushStoreKey ?? '');
    const apiUrl = @json($coinrushApiUrl ?? 'https://coinrush.link/store');
    const successUrl = @json(route('shortlink.payment-tron-success'));
    const prepareUrl = @json(route('shortlink.payment-tron-prepare'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value;
    if (!window.TronPayment) { alert('Payment widget failed to load. Please refresh.'); return; }
    btn.disabled = true; btn.textContent = 'Preparing...';
    let orderId, amount;
    try {
        const res = await fetch(prepareUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf || '', 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({}), credentials: 'same-origin' });
        const data = await res.json();
        if (!res.ok || !data.order_id) throw new Error(data.error || data.message || 'Failed');
        orderId = data.order_id; amount = data.amount;
    } catch (e) {
        btn.disabled = false; btn.textContent = 'Continue with Tron';
        alert(e.message || 'Failed to prepare payment.'); return;
    }
    btn.disabled = false; btn.textContent = 'Continue with Tron';
    TronPayment.init({ storeKey, apiUrl });
    TronPayment.openPayment({ transactionId: orderId, amount, asset: 'USDT', onSuccess: () => { window.location.href = successUrl + '?order_id=' + orderId; }, onError: (e) => alert(e?.message || 'Payment failed.'), onCancel: () => {} });
});
</script>
@endif
@endpush
