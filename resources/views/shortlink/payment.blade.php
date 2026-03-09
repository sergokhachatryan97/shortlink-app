<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="heleket" content="89c70a02" />
    <title>Complete Payment - Shortlink</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --brand: #6366f1; --accent: #8b5cf6; --radius: 12px; }
        body { font-family: 'DM Sans', sans-serif; background: #f8fafc; min-height: 100vh; }
        .payment-page { padding-top: 64px; padding-bottom: 2rem; }
        .payment-card { background: #fff; border-radius: var(--radius); box-shadow: 0 1px 3px rgba(0,0,0,.06); border: none; height: 100%; }
        .payment-card .card-body { padding: 1.5rem; }
        .total-amount { font-size: 1.5rem; font-weight: 700; color: var(--brand); }
        .payment-option-card { background: #fff; border-radius: var(--radius); box-shadow: 0 1px 3px rgba(0,0,0,.06); border: 1px solid #e2e8f0; height: 100%; transition: box-shadow .2s; }
        .payment-option-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.08); }
        .payment-option-card .card-body { padding: 1.5rem; display: flex; flex-direction: column; }
        .payment-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.25rem; margin-bottom: 1rem; }
        .payment-icon-tron { background: linear-gradient(135deg, #ede9fe, #e9d5ff); color: #7c3aed; }
        .payment-icon-tron .payment-icon-img { object-fit: contain; }
        .payment-icon-heleket { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #dc2626; }
        .check-item { display: flex; align-items: center; gap: 8px; font-size: 0.875rem; margin-bottom: 6px; }
        .check-item .check-icon { color: #6366f1; flex-shrink: 0; }
        .check-item.text-muted .check-icon { color: #94a3b8; }
        .btn-tron { background: linear-gradient(90deg, #6d28d9, #8b5cf6); color: white !important; border: none; border-radius: 10px; font-weight: 600; padding: 10px 20px; }
        .btn-tron:hover { color: white !important; opacity: 0.95; }
        .btn-heleket { background: linear-gradient(90deg, #dc2626, #ef4444); color: white !important; border: none; border-radius: 10px; font-weight: 600; padding: 10px 20px; }
        .btn-heleket:hover { color: white !important; opacity: 0.95; }
        .secure-footer { background: #fff; border-radius: var(--radius); box-shadow: 0 1px 3px rgba(0,0,0,.06); padding: 1rem 1.5rem; }
        .secure-footer .logo-box { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.75rem; }
        .logo-cr { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; }
        .logo-heleket { background: linear-gradient(135deg, #dc2626, #ef4444); color: white; }
    </style>
</head>
<body class="min-vh-100 payment-page">
    @include('components.navbar')
    <div class="container py-4" style="max-width: 900px;">
        <a href="{{ route('shortlink.index') }}" class="btn btn-link text-decoration-none d-inline-flex align-items-center gap-1 mb-4 px-0" style="color: #64748b;">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back
        </a>

        <h1 class="fw-bold mb-1" style="font-size: 1.75rem; color: #1e293b;">Complete Payment</h1>
        <p class="text-muted mb-4">
            @if($freeTrialExhausted ?? false)
                Your free trial has ended. Pay to generate {{ $count }} short links.
            @else
                Pay to generate {{ $count }} short links ({{ $count - ($remaining ?? 0) }} over your {{ $remaining ?? 0 }} free).
            @endif
        </p>

        @if (session('error'))
            <div class="alert alert-danger border-0 mb-4" style="border-radius: var(--radius);">{{ session('error') }}</div>
        @endif

        @php($tronAvailable = !empty($coinrushStoreKey ?? null))
        @if(!$tronAvailable && !($heleketAvailable ?? false))
            <div class="alert alert-warning border-0 mb-4" style="border-radius: var(--radius);">
                Configure <code>COINRUSH_STORE_KEY</code> or <code>HELEKET_MERCHANT</code> / <code>HELEKET_PAYMENT_KEY</code> in <code>.env</code> to enable payments.
            </div>
        @endif

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="payment-card card">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3" style="color: #1e293b;">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="me-1 d-inline"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Payment Summary
                        </h5>
                        <p class="mb-1 small text-muted">URL</p>
                        <p class="mb-3 text-break" style="font-size: 0.9375rem; color: #334155;">{{ Str::limit($url ?? '', 40) }}</p>
                        <p class="mb-1 small text-muted">Quantity</p>
                        <p class="mb-3" style="color: #334155;">{{ $count }} links</p>
                        <p class="mb-1 small text-muted">Price per link</p>
                        <p class="mb-3" style="color: #334155;">${{ number_format($pricePerLink ?? 0.001, 3) }} per link</p>
                        <p class="total-amount mb-0">${{ number_format($amount, 3) }}</p>
                        <p class="small text-muted mt-3 mb-0">After payment, your links will be generated automatically.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="payment-option-card card">
                    <div class="card-body">
                        <h5 class="fw-bold mb-2" style="color: #1e293b;">Pay with Tron (USDT / TRX)</h5>
                        <div class="payment-icon payment-icon-tron"><img src="{{ asset('images/tron-logo.png') }}" alt="Tron" width="32" height="32" class="payment-icon-img"></div>
                        <p class="small text-muted mb-3">Fast USDT or TRX payment on Tron network</p>
                        <div class="check-item"><span class="check-icon">✓</span> Secure and quick on-chain payment</div>
                        <div class="check-item text-muted"><span class="check-icon">✓</span> Low fees</div>
                        <div class="mt-auto pt-3">
                            @if($tronAvailable)
                                <button type="button" id="btn-tron" class="btn btn-tron w-100">Continue with Tron</button>
                            @else
                                <button type="button" class="btn btn-tron w-100" disabled style="opacity: 0.6;">Continue with Tron</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="payment-option-card card">
                    <div class="card-body">
                        <h5 class="fw-bold mb-2" style="color: #1e293b;">Pay with Heleket (Crypto)</h5>
                        <div class="payment-icon payment-icon-heleket">H</div>
                        <p class="small text-muted mb-3">Pay via crypto checkout</p>
                        <div class="check-item"><span class="check-icon">✓</span> Supports Bitcoin, Ethereum, and more</div>
                        <div class="check-item text-muted"><span class="check-icon">✓</span> Secure checkout</div>
                        <div class="mt-auto pt-3">
                            @if($heleketAvailable ?? false)
                                <form method="POST" action="{{ route('shortlink.payment.initiate') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-heleket w-100">Continue with Heleket</button>
                                </form>
                            @else
                                <button type="button" class="btn btn-heleket w-100" disabled style="opacity: 0.6;">Continue with Heleket</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="secure-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <span class="text-success">✓</span>
                <span class="fw-medium" style="color: #1e293b;">Secure payment via CoinRush (Tron) and Heleket</span>
            </div>
            <p class="small text-muted mb-0">You will return to Shortlink after payment.</p>
            <div class="d-flex align-items-center gap-2 ms-auto">
                <span class="logo-box logo-cr">CR</span>
                <span class="logo-box logo-heleket">H</span>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @if($tronAvailable ?? false)
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
</body>
</html>
