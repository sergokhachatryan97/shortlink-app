<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Required - Shortlink Generator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --brand: #6366f1;
            --brand-hover: #4f46e5;
            --accent: #8b5cf6;
            --radius: 12px;
            --radius-sm: 8px;
        }
        body {
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 50%, #e2e8f0 100%);
            min-height: 100vh;
        }
        .payment-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,.05), 0 4px 6px -4px rgba(0,0,0,.04);
            border: none;
        }
        .details-box {
            background: linear-gradient(145deg, #faf5ff 0%, #f5f3ff 100%);
            border: 1px solid #e9d5ff;
            border-radius: var(--radius-sm);
        }
        .amount-display {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--brand);
        }
        .btn-pay {
            background: linear-gradient(135deg, #eab308 0%, #ca8a04 100%);
            border: none;
            color: #1e293b;
            font-weight: 600;
            padding: 14px 24px;
            border-radius: var(--radius-sm);
            box-shadow: 0 4px 14px rgba(234, 179, 8, 0.4);
            transition: transform .2s, box-shadow .2s;
        }
        .btn-pay:hover {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #1e293b;
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.45);
            transform: translateY(-1px);
        }
        .btn-tron {
            background: linear-gradient(135deg, #ff3d71 0%, #c41e3a 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 14px 24px;
            border-radius: var(--radius-sm);
            box-shadow: 0 4px 14px rgba(255, 61, 113, 0.4);
            transition: transform .2s, box-shadow .2s;
        }
        .btn-tron:hover {
            background: linear-gradient(135deg, #ff6b8a 0%, #e63950 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(255, 61, 113, 0.45);
            transform: translateY(-1px);
        }
        .footer-link { font-size: 0.875rem; color: #64748b; }
    </style>
</head>
<body class="min-vh-100 d-flex align-items-center justify-content-center p-4">
    <div class="container" style="max-width: 460px;">
        <a href="{{ route('shortlink.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 mb-4" style="border-radius: var(--radius-sm);">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back
        </a>

        <div class="payment-card p-5 mb-4">
            <h1 class="fw-bold mb-3" style="font-size: 1.5rem; color: #1e293b;">Payment Required</h1>
            <p class="text-muted mb-4" style="font-size: 1rem;">
                @if($freeTrialExhausted ?? false)
                    You have already used your free trial. Generating <strong class="text-dark">{{ $count }}</strong> links requires payment.
                @else
                    Generating <strong class="text-dark">{{ $count }}</strong> links ({{ $count - $freeLimit }} over free limit of {{ $freeLimit }}) requires payment.
                @endif
            </p>

            @if (session('error'))
                <div class="alert alert-danger py-3 mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="details-box p-4 mb-4">
                <p class="text-muted mb-1 small">URL</p>
                <p class="mb-3 text-break" style="font-size: 0.9375rem; color: #334155;">{{ $url }}</p>
                <p class="text-muted mb-1 small">Amount</p>
                <p class="amount-display mb-0">${{ number_format($amount, 2) }} <span class="fs-6 fw-normal text-muted">USD</span></p>
            </div>

            <div class="mb-3">
                <label class="form-label fw-medium mb-2">Payment method</label>
                <div class="btn-group w-100" role="group" aria-label="Payment gateway">
                    <input type="radio" class="btn-check" name="gateway" id="gw-heleket" autocomplete="off" checked>
                    <label class="btn btn-outline-secondary" for="gw-heleket" style="border-radius: var(--radius-sm) 0 0 var(--radius-sm);">Heleket</label>

                    @php($tronAvailable = !empty($coinrushStoreKey ?? null))
                    <input type="radio" class="btn-check" name="gateway" id="gw-tron" autocomplete="off" {{ $tronAvailable ? '' : 'disabled' }}>
                    <label class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2"
                           for="gw-tron"
                           style="border-radius: 0 var(--radius-sm) var(--radius-sm) 0; {{ $tronAvailable ? '' : 'opacity:.6; cursor:not-allowed;' }}">
                        Tron (CoinRush)
                        @if(!$tronAvailable)
                            <span class="badge text-bg-light border">Not configured</span>
                        @endif
                    </label>
                </div>
                <div class="small text-muted mt-2">
                    Choose a gateway, then click Pay.
                    @if(!$tronAvailable)
                        <div class="mt-1">
                            To enable Tron, set <code>COINRUSH_STORE_KEY</code> in <code>.env</code>.
                        </div>
                    @endif
                </div>
            </div>

            <div class="d-flex flex-column gap-2">
                <div id="pay-heleket">
                    <form method="POST" action="{{ route('shortlink.payment.initiate') }}">
                        @csrf
                        <button type="submit" class="btn btn-pay w-100 btn-lg">
                            Pay with Crypto (Heleket)
                        </button>
                    </form>
                </div>

                <div id="pay-tron" style="display: none;">
                    @if($tronAvailable)
                        <button type="button" id="btn-tron" class="btn btn-tron w-100 btn-lg">
                            Pay with Tron (USDT)
                        </button>
                    @else
                        <button type="button" class="btn btn-tron w-100 btn-lg" disabled style="opacity:.6;">
                            Pay with Tron (USDT)
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <p class="footer-link text-center mb-0">
            Powered by <a href="https://doc.heleket.com/" target="_blank" rel="noopener" style="color: var(--brand);">Heleket</a> & <a href="https://coinrush.link" target="_blank" rel="noopener" style="color: var(--brand);">CoinRush</a> – Bitcoin, ETH, USDT, TRX & more
        </p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const tronAvailable = @json($tronAvailable ?? false);

        function setGateway(gateway) {
            const heleket = document.getElementById('pay-heleket');
            const tron = document.getElementById('pay-tron');

            if (gateway === 'tron') {
                if (!tronAvailable) {
                    setGateway('heleket');
                    return;
                }
                if (heleket) heleket.style.display = 'none';
                if (tron) tron.style.display = '';
            } else {
                if (heleket) heleket.style.display = '';
                if (tron) tron.style.display = 'none';
            }
        }

        document.getElementById('gw-heleket')?.addEventListener('change', function() {
            if (this.checked) setGateway('heleket');
        });
        document.getElementById('gw-tron')?.addEventListener('change', function() {
            if (this.checked) setGateway('tron');
        });

        setGateway(document.getElementById('gw-tron')?.checked ? 'tron' : 'heleket');
    </script>
    @if($tronAvailable)
    <script src="{{ asset('js/tron-payment.js') }}"></script>
    <script>
        document.getElementById('btn-tron')?.addEventListener('click', function() {
            const storeKey = @json($coinrushStoreKey);
            const apiUrl = @json($coinrushApiUrl ?? 'https://coinrush.link/store');
            const amount = {{ $amount }};
            const successUrl = @json(route('shortlink.payment-tron-success'));
            const orderId = 'sl-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10);

            if (!window.TronPayment) {
                alert('Payment widget failed to load. Please refresh and try again.');
                return;
            }

            TronPayment.init({ storeKey: storeKey, apiUrl: apiUrl });

            TronPayment.openPayment({
                transactionId: orderId,
                amount: amount,
                asset: 'USDT',
                onSuccess: function(payment) {
                    const params = new URLSearchParams({
                        order_id: orderId,
                        amount_usd: payment.amount_usd ?? amount,
                        transaction_id: payment.transaction_id ?? '',
                    });
                    window.location.href = successUrl + '?' + params.toString();
                },
                onError: function(error) {
                    alert(error?.message || 'Payment failed. Please try again.');
                },
                onCancel: function() {}
            });
        });
    </script>
    @endif
</body>
</html>
