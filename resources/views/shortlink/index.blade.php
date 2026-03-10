<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="heleket" content="89c70a02" />
    <title>Trastly – Create Trusted Short Links</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --brand: #6366f1;
            --brand-hover: #4f46e5;
            --accent: #8b5cf6;
            --surface: #ffffff;
            --bg: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 50%, #e2e8f0 100%);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,.05), 0 4px 6px -4px rgba(0,0,0,.04);
            --radius: 12px;
            --radius-sm: 8px;
        }
        body {
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0a0a12 url('{{ asset('images/hero-bg.png') }}') no-repeat center center;
            background-size: cover;
            background-attachment: fixed;
            min-height: 100vh;
            color: #fff;
        }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: linear-gradient(180deg, rgba(10,10,18,0.75) 0%, rgba(10,10,18,0.9) 100%);
            pointer-events: none;
            z-index: 0;
        }
        body > * { position: relative; z-index: 1; }
        .hero-title {
            font-size: 2.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #fff;
        }
        .hero-sub {
            color: rgba(255,255,255,0.75);
            font-size: 1.0625rem;
            text-align: center;
            width: 85%;
            margin: auto;
        }
        .card-style {
            background: rgba(30, 30, 45, 0.1);
            border-radius: var(--radius);
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.08);
            font-size: 1rem;
            color: #e2e8f0;
        }
        .card-style .form-control { font-size: 1rem; background: rgba(15,15,25,0.1); border-color: rgba(255,255,255,0.15); color: #fff; }
        .card-style .form-control::placeholder { color: rgba(255,255,255,0.4); }
        .card-style .form-label { font-size: 0.9375rem; color: #e2e8f0; }
        .card-style .form-text { font-size: 0.75rem; color: rgba(255,255,255,0.6); }
        .card-style .input-group-text { font-size: 1rem; background: rgba(15,15,25,0.1); border-color: rgba(255,255,255,0.15); color: #94a3b8; }
        .form-control, .input-group-text {
            border-radius: var(--radius-sm) !important;
            border-color: rgba(255,255,255,0.15);
        }
        .form-control:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.35);
            background: rgba(15,15,25,0.8);
            color: #fff;
        }
        .card-style .form-control:-webkit-autofill,
        .card-style .form-control:-webkit-autofill:hover,
        .card-style .form-control:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 1000px rgba(15,15,25,0.8) inset !important;
            -webkit-text-fill-color: #fff !important;
        }
        .input-group-quantity { max-width: 160px; }
        .input-group-quantity .form-control {
            text-align: center;
            font-weight: 600;
            border-radius: 0 !important;
        }
        .input-group-quantity .btn:first-child {
            border-radius: var(--radius-sm) 0 0 var(--radius-sm);
        }
        .input-group-quantity .btn:last-child {
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
        }
        .input-group-quantity .btn {
            font-weight: 600;
            padding: 8px 14px;
            font-size: 1rem;
            background: rgba(30,30,45,0.9);
            border-color: rgba(255,255,255,0.15);
            color: #e2e8f0;
        }
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] {
            -moz-appearance: textfield;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--brand) 0%, var(--accent) 100%);
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            padding: 9px 20px;
            font-size: 1.0625rem;
            box-shadow: 0 3px 10px rgba(99, 102, 241, 0.35);
            transition: transform .2s, box-shadow .2s;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--brand-hover) 0%, #7c3aed 100%);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
            transform: translateY(-1px);
        }
        .progress-bar-custom {
            height: 10px;
            background: rgba(255,255,255,0.15);
            border-radius: 999px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4d2bf1, #560f93);
            border-radius: 999px;
            transition: width 0.4s ease;
        }
        .links-box {
            background: rgba(30, 30, 45, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            font-size: 1rem;
            color: #e2e8f0;
        }
        .link-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            font-size: 1rem;
        }
        .link-row:last-child { border-bottom: none; }
        .link-url {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #e2e8f0;
        }
        .plan-status { font-size: 0.8125rem; color: rgba(255,255,255,0.7); }
        .plan-status #plan-text { font-weight: 500; color: rgba(255,255,255,0.7); font-size: 0.8125rem; }
        .plan-status-row { min-height: 24px; }
        .plan-status-left, .plan-status-right { color: rgba(255,255,255,0.6); font-size: 0.8125rem; font-weight: 500; white-space: nowrap; }
        .btn-copy {
            font-size: 0.875rem;
            padding: 6px 14px;
            border-radius: 8px;
            font-weight: 500;
        }
        .footer-note {
            color: #94a3b8;
            font-size: 0.9375rem;
        }
        .page-btn {
            padding: 4px 10px;
            font-size: 0.875rem;
            border-radius: 6px;
        }
        :root { --navbar-height: 72px; }
        @media (max-width: 991.98px) { :root { --navbar-height: 80px; } }
        body { padding-top: var(--navbar-height) !important; padding-bottom: 2rem; }
        .landing-page .navbar { background: rgba(10,10,18,0.6) !important; border-color: rgba(255,255,255,0.06) !important; }
        .landing-page .navbar .navbar-brand { color: #fff !important; }
        .landing-page .navbar .nav-link { color: rgba(255,255,255,0.8) !important; }
        .landing-page .navbar .nav-link:hover { color: #fff !important; }
        .landing-page .navbar .nav-link.active { color: #a78bfa !important; }
        .landing-page .navbar .btn-outline-secondary { border-color: rgba(255,255,255,0.4); color: #fff; }
        .landing-page .navbar .btn-outline-secondary:hover { background: rgba(255,255,255,0.1); color: #fff; border-color: rgba(255,255,255,0.5); }
        .landing-page .navbar .dropdown-toggle { background: rgba(255,255,255,0.1) !important; color: #fff !important; }
        .landing-page .navbar .balance-amount { color: #fff !important; }
        .landing-page .navbar .navbar-toggler { border-color: rgba(255,255,255,0.4); }
        .landing-page .navbar .navbar-toggler-icon { filter: invert(1); }
        .landing-page .navbar .dropdown-menu { background: rgba(30,30,45,0.95) !important; border: 1px solid rgba(255,255,255,0.1) !important; }
        .landing-page .navbar .dropdown-item { color: rgba(255,255,255,0.9) !important; }
        .landing-page .navbar .dropdown-item:hover { background: rgba(255,255,255,0.1) !important; color: #fff !important; }
        .footer-contact-landing { border-top: 1px solid rgba(255,255,255,0.1); }
        .pricing-card-landing {
            background: rgba(30, 30, 45, 0.6);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
        }
        .pricing-plan-name { color: rgba(255,255,255,0.6); font-size: 0.875rem; font-weight: 500; }
        .pricing-plan-price { color: #fff; font-size: 1.5rem; font-weight: 700; }
        .pricing-view-btn {
            background: rgba(30, 30, 45, 0.6);
            border: 1px solid rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.7);
            font-size: 0.875rem;
            font-weight: 500;
            padding: 10px 24px;
            border-radius: 10px;
        }
        .pricing-view-btn:hover { background: rgba(40,40,60,0.8); color: #fff; border-color: rgba(255,255,255,0.3); }
    </style>
</head>
<body class="min-vh-100 landing-page">
    @include('components.navbar')
    <div class="container" style="max-width: 600px;">
        <header class="mb-4 mt-4">
            <h1 class="hero-title mb-1">Create Trusted Short Links Instantly</h1>
            <p class="hero-sub mb-0">High-trust domain redirects for campaigns, traffic distribution, and link management.</p>
        </header>

        @if (session('success'))
            <div class="alert alert-success mb-4 py-3">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger mb-4 py-3">{{ session('error') }}</div>
        @endif

        <div id="plan-limit-alert" class="alert alert-warning mb-4 py-3 align-items-center" style="display: {{ ($atPlanLimit ?? false) ? 'flex' : 'none' }};">
            <span class="me-2">⚠️</span>
            <div>
                <strong>Plan limit reached.</strong> Generated links will be deducted from your balance.
                <span class="d-block mt-1 text-muted small">$<span id="price-per-link">{{ number_format($pricePerLink ?? 0.01, 2) }}</span> per link</span>
            </div>
        </div>

        <div class="card-style p-5 mb-4">
            <form id="shortlink-form">
                @csrf
                <input type="hidden" name="fingerprint" id="fingerprint" value="">

                <div class="mb-4">
                    <label for="url" class="form-label fw-medium">Destination URL</label>
                    <div class="input-group">
                        <input type="url" id="url" name="url" required placeholder="https://example.com"
                               class="form-control">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="count" class="form-label fw-medium">Number of links</label>
                    <div class="input-group input-group-quantity">
                        <button type="button" class="btn btn-outline-secondary" id="qty-minus">−</button>
                        <input type="number" id="count" name="count" required min="1" max="1000" value="50"
                               class="form-control">
                        <button type="button" class="btn btn-outline-secondary" id="qty-plus">+</button>
                    </div>
                    <p class="form-text text-white mt-1 small">Max 50 for free trial. Above requires payment. <strong>$<span id="form-price-per-link">{{ number_format($pricePerLink ?? 0.001, 3) }}</span> per link</strong></p>
                </div>

                <button type="submit" id="generate-btn" class="btn btn-primary btn-lg w-100 mb-4">
                    Generate Trust Links
                </button>
            </form>

            <div class="plan-status" id="plan-status">
                @if ($planName ?? null)
                <p class="mb-1" id="plan-text">Plan: {{ $planName }} — @if (($planLimit ?? 0) > 0){{ $planUsed ?? 0 }} / {{ $planLimit }} used @else Unlimited @endif</p>
                @if (($planLimit ?? 0) > 0)
                <div class="progress-bar-custom mb-1">
                    <div class="progress-fill" id="progress-fill" style="width: {{ min(100, (($planUsed ?? 0) / max(1, $planLimit ?? 1)) * 100) }}%;"></div>
                </div>
                @endif
                @else
                <div style="display: flex; justify-content: space-between">
                    <span class="plan-status-left">Free trial remaining</span>
                    <span class="plan-status-right"><span id="remaining">{{ $remaining ?? 50 }}</span> / 50 links</span>
                </div>
                <div class="plan-status-row d-flex align-items-center gap-2 mb-1">
                    <div class="progress-bar-custom flex-grow-1">
                        <div class="progress-fill" id="progress-fill" style="width: {{ (($remaining ?? 50) / 50) * 100 }}%;"></div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div id="links-section" class="links-box mb-4" style="display: none;">
            <div class="d-flex align-items-center gap-2 mb-3">
                <input type="radio" checked class="form-check-input" id="plan-radio">
                <label for="plan-radio" class="form-check-label mb-0" id="links-label" style="font-size: 0.8125rem; color: rgba(255,255,255,0.7);">
                    @if ($planName ?? null)
                        Plan: {{ $planName }} ({{ $planUsed ?? 0 }}{{ ($planLimit ?? 0) > 0 ? ' / ' . $planLimit : '' }})
                    @else
                        Free trial remaining — <span id="remaining-2">{{ $remaining ?? 0 }}</span> / 50 links
                    @endif
                </label>
            </div>
            <div id="links-list"></div>
            <nav id="links-pagination" class="mt-3 d-flex justify-content-center align-items-center gap-2 flex-wrap" style="display: none;"></nav>
            <div class="mt-3 pt-3 border-top border-success border-opacity-25 d-flex flex-wrap gap-2">
                <button type="button" id="copy-all-links" class="btn btn-outline-secondary" style="border-radius: 8px;">Copy all links</button>
                <a href="#" id="download-csv" class="btn" style="background: #059669; color: white; border-radius: 8px;">Download all as CSV</a>
            </div>
        </div>
    </div>

    <div class="container mt-5 pt-4" style="max-width: 960px;">
        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <h5 class="text-white fw-bold mb-3">Use cases</h5>
                <ul class="list-unstyled mb-0">
                    <li class="d-flex align-items-center gap-2 mb-2" style="color: rgba(255,255,255,0.85);"><span class="text-success">✓</span> Affiliate campaigns</li>
                    <li class="d-flex align-items-center gap-2 mb-2" style="color: rgba(255,255,255,0.85);"><span class="text-success">✓</span> Traffic distribution</li>
                    <li class="d-flex align-items-center gap-2 mb-2" style="color: rgba(255,255,255,0.85);"><span class="text-success">✓</span> Media buying teams</li>
                    <li class="d-flex align-items-center gap-2" style="color: rgba(255,255,255,0.85);"><span class="text-success">✓</span> Telegram marketers</li>
                </ul>
            </div>
        </div>

        @if ($plans ?? null)
        <h5 class="text-center mb-4" style="color: rgba(255,255,255,0.6); font-size: 1rem; font-weight: 500;">Pricing</h5>
        <div class="row g-3 mb-4 justify-content-center card-style" style="padding: 15px 0 25px 0">
            @foreach($plans as $plan)
            <div class="col-6 col-md-4 col-lg-3">
                <div class="pricing-card-landing text-center py-4 px-3">
                    <p class="mb-1 pricing-plan-name">{{ $plan->name }}</p>
                    <p class="mb-0 pricing-plan-price">${{ $plan->price_usd == (int) $plan->price_usd ? number_format($plan->price_usd, 0) : number_format($plan->price_usd, 2) }}</p>
                </div>
            </div>
            @endforeach
        </div>
        <div class="text-center">
            <a href="{{ route('subscription.index') }}" class="btn pricing-view-btn">View Pricing</a>
        </div>
        @endif
    </div>

    <footer class="footer-contact-landing py-4 mt-5">
        <div class="container">
            <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-md-between gap-3">
                <span class="small" style="color: rgba(255,255,255,0.5);">&copy; {{ date('Y') }} {{ config('app.name') }}</span>
                <div class="d-flex align-items-center gap-4 flex-wrap">
                    <a href="{{ route('contact.index') }}" class="small text-decoration-none d-inline-flex align-items-center gap-1" style="color: #a78bfa;">Contact</a>
                    <a href="mailto:{{ config('app.support_email') }}" class="small text-decoration-none d-inline-flex align-items-center gap-1" style="color: rgba(255,255,255,0.6);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        {{ config('app.support_email') }}
                    </a>
                    @if(config('app.support_telegram'))
                    <a href="{{ config('app.support_telegram') }}" target="_blank" rel="noopener" class="small text-decoration-none d-inline-flex align-items-center gap-1" style="color: rgba(255,255,255,0.6);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                        Telegram
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </footer>

    <div class="modal fade" id="pricingModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
                <div class="modal-header border-0 pb-2 pt-4 px-4">
                    <h5 class="modal-title fw-bold" style="color: #1e293b;">Pricing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 pb-4 pt-0">
                    <p class="mb-3" style="color: #334155; font-size: 1rem;"><strong>Free trial:</strong> Up to 50 links, one-time per device.</p>
                    <p class="mb-0" style="color: #334155; font-size: 1rem;"><strong>Paid:</strong> More than 50 links or after trial — pay per link via Heleket or Tron (crypto).</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function simpleFingerprint() {
            const data = [
                (screen.width || 0) + 'x' + (screen.height || 0),
                (screen.availWidth || 0) + 'x' + (screen.availHeight || 0),
                (screen.colorDepth || 0),
                new Date().getTimezoneOffset(),
                (navigator.hardwareConcurrency || 0),
                navigator.language
            ].join('|');
            let hash = 0;
            for (let i = 0; i < data.length; i++) {
                hash = ((hash << 5) - hash) + data.charCodeAt(i);
                hash = hash >>> 0;
            }
            return 'device_' + hash.toString(36);
        }
        document.getElementById('fingerprint').value = simpleFingerprint();

        @php
            $initialPlanData = [
                'plan_name' => $planName ?? null,
                'plan_limit' => $planLimit ?? 50,
                'plan_used' => $planUsed ?? 0,
                'plan_remaining' => $planRemaining ?? $remaining ?? 50,
                'remaining' => $remaining ?? 50,
            ];
        @endphp
        const initialPlan = @json($initialPlanData);
        const pricePerLink = {{ $pricePerLink ?? 0.01 }};
        updatePlanStatus(initialPlan);

        document.getElementById('qty-minus').addEventListener('click', () => {
            const el = document.getElementById('count');
            const v = Math.max(1, parseInt(el.value) - 1);
            el.value = v;
        });
        document.getElementById('qty-plus').addEventListener('click', () => {
            const el = document.getElementById('count');
            const v = Math.min(1000, parseInt(el.value) + 1);
            el.value = v;
        });

        function updatePlanStatus(data) {
            const planName = data.plan_name;
            const planLimit = data.plan_limit ?? 50;
            const planUsed = data.plan_used ?? 0;
            const remaining = data.remaining ?? data.plan_remaining ?? 0;

            const planText = document.getElementById('plan-text');
            const linksLabel = document.getElementById('links-label');
            const progressFill = document.getElementById('progress-fill');
            const progressBar = progressFill?.closest('.progress-bar-custom');

            if (planName) {
                const usedText = planLimit > 0 ? planUsed + ' / ' + planLimit + ' used' : 'Unlimited';
                if (planText) planText.innerHTML = 'Plan: ' + escapeHtml(planName) + ' — ' + usedText;
                if (linksLabel) linksLabel.innerHTML = 'Plan: ' + escapeHtml(planName) + ' (' + planUsed + (planLimit > 0 ? ' / ' + planLimit : '') + ')';
            } else {
                const remEl = document.getElementById('remaining');
                const rem2El = document.getElementById('remaining-2');
                if (remEl) remEl.textContent = remaining;
                if (rem2El) rem2El.textContent = remaining;
                if (linksLabel) linksLabel.innerHTML = 'Free trial remaining — <span id="remaining-2">' + remaining + '</span> / 50 links';
            }

            if (progressBar && progressFill) {
                const pct = planLimit > 0 ? (planName ? (planUsed / planLimit) : (remaining / 50)) * 100 : 0;
                progressFill.style.width = Math.min(100, pct) + '%';
            }

            const planLimitAlert = document.getElementById('plan-limit-alert');
            if (planLimitAlert) {
                // Only show when plan limit is fully exhausted; hide when they still have remaining links
                const atPlanLimit = planName && planLimit > 0 && planUsed >= planLimit;
                planLimitAlert.style.display = atPlanLimit ? 'flex' : 'none';
            }
        }

        function escapeHtml(s) {
            const d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {});
        }

        const LINKS_PER_PAGE = 20;
        let allLinks = @json($links ?? []);
        let currentPage = 1;
        const fromPaymentRedirect = @json(session('download_ready', false));
        const paymentProvider = @json(session('payment_provider', ''));

        document.getElementById('copy-all-links').addEventListener('click', () => {
            if (allLinks.length === 0) return;
            copyToClipboard(allLinks.join('\n'));
            const btn = document.getElementById('copy-all-links');
            const orig = btn.textContent;
            btn.textContent = 'Copied!';
            setTimeout(() => { btn.textContent = orig; }, 1500);
        });

        if (allLinks.length > 0) {
            document.getElementById('links-section').style.display = 'block';
            document.getElementById('download-csv').href = '{{ route('shortlink.download') }}';
            if (fromPaymentRedirect && paymentProvider) {
                document.getElementById('plan-radio').style.display = 'none';
                const providerLabel = paymentProvider === 'heleket' ? 'Heleket' : 'Tron';
                document.getElementById('links-label').innerHTML = '<span class="text-success">Paid (' + providerLabel + ')</span> — ' + allLinks.length + ' links generated';
            }
            renderLinksPage(1);
        }

        function renderLinksPage(page) {
            currentPage = page;
            const list = document.getElementById('links-list');
            list.innerHTML = '';
            const start = (page - 1) * LINKS_PER_PAGE;
            const end = Math.min(start + LINKS_PER_PAGE, allLinks.length);
            const pageLinks = allLinks.slice(start, end);

            pageLinks.forEach(link => {
                const row = document.createElement('div');
                row.className = 'link-row';
                row.innerHTML = '<span class="link-url" title="' + link.replace(/"/g, '&quot;') + '">' + link + '</span>' +
                    '<button type="button" class="btn btn-copy btn-outline-secondary" data-link="' + link.replace(/"/g, '&quot;') + '">Copy</button>';
                list.appendChild(row);
            });

            document.querySelectorAll('#links-list .btn-copy').forEach(b => {
                b.addEventListener('click', () => {
                    copyToClipboard(b.dataset.link);
                    const orig = b.textContent;
                    b.textContent = 'Copied!';
                    setTimeout(() => { b.textContent = orig; }, 1500);
                });
            });

            renderPagination();
        }

        function renderPagination() {
            const nav = document.getElementById('links-pagination');
            const totalPages = Math.ceil(allLinks.length / LINKS_PER_PAGE);

            if (totalPages <= 1) {
                nav.style.display = 'none';
                return;
            }
            nav.style.display = 'flex';

            let html = '';
            if (currentPage > 1) {
                html += '<button type="button" class="btn btn-outline-secondary page-btn" id="links-prev">Prev</button>';
            }
            html += '<span class="small text-muted">Page ' + currentPage + ' of ' + totalPages + '</span>';
            if (currentPage < totalPages) {
                html += '<button type="button" class="btn btn-outline-secondary page-btn" id="links-next">Next</button>';
            }
            nav.innerHTML = html;

            document.getElementById('links-prev')?.addEventListener('click', () => renderLinksPage(currentPage - 1));
            document.getElementById('links-next')?.addEventListener('click', () => renderLinksPage(currentPage + 1));
        }

        document.getElementById('shortlink-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('generate-btn');
            const fpInput = document.getElementById('fingerprint');
            if (!fpInput.value) fpInput.value = simpleFingerprint();

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing';

            try {
                const formData = new FormData(e.target);
                const res = await fetch('{{ route('shortlink.generate') }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });

                const data = await res.json();

                if (data.requires_payment && data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }

                if (data.success) {
                    if (data.balance !== undefined) {
                        const balEl = document.getElementById('balance-amount');
                        if (balEl) balEl.textContent = '$' + parseFloat(data.balance).toFixed(2);
                    }
                    if (data.plan_name !== undefined || data.remaining !== undefined) {
                        updatePlanStatus(data);
                    }

                    if (data.links && data.links.length > 0) {
                        allLinks = data.links;
                        currentPage = 1;
                        renderLinksPage(1);

                        document.getElementById('links-section').style.display = 'block';
                        document.getElementById('download-csv').href = data.download_url || '{{ route('shortlink.download') }}';
                    } else if (data.download_url) {
                        const a = document.createElement('a');
                        a.href = data.download_url;
                        a.download = 'shortlinks.csv';
                        a.click();
                    }
                } else {
                    alert(data.message || 'Something went wrong');
                }
            } catch (err) {
                alert('Something went wrong. Please try again.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Generate Links';
            }
        });

    </script>
</body>
</html>
