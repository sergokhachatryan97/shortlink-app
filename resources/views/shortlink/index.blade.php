<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="heleket" content="89c70a02" />
    <title>Shortlink Generator</title>
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
            background: var(--bg);
            min-height: 100vh;
        }
        .hero-title {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #1e293b;
        }
        .hero-sub {
            color: #64748b;
            font-size: 1.0625rem;
        }
        .card-style {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            border: none;
            font-size: 1rem;
        }
        .card-style .form-control { font-size: 1rem; }
        .card-style .form-label { font-size: 0.9375rem; }
        .card-style .form-text { font-size: 0.75rem; }
        .card-style .input-group-text { font-size: 1rem; }
        .form-control, .input-group-text {
            border-radius: var(--radius-sm) !important;
            border-color: #e2e8f0;
        }
        .form-control:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
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
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #22c55e, #10b981);
            border-radius: 999px;
            transition: width 0.4s ease;
        }
        .links-box {
            background: linear-gradient(145deg, #f0fdf4 0%, #ecfdf5 100%);
            border: 1px solid #a7f3d0;
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,.04);
            font-size: 1rem;
        }
        .link-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(34, 197, 94, 0.2);
            font-size: 1rem;
        }
        .link-row:last-child { border-bottom: none; }
        .link-url {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #334155;
        }
        .plan-status { font-size: 0.9375rem; }
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
    </style>
</head>
<body class="min-vh-100">
    @include('components.navbar')
    <div class="container" style="max-width: 600px;">
        <header class="mb-4 mt-4">
            <h1 class="hero-title mb-1">Shortlink Generator</h1>
            <p class="hero-sub mb-0">Create Short Links — Generate multiple shortened URLs instantly.</p>
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
                    <label for="url" class="form-label fw-medium">Link (URL)</label>
                    <div class="input-group">
                        <input type="url" id="url" name="url" required placeholder="https://example.com"
                               class="form-control">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="count" class="form-label fw-medium">Quantity</label>
                    <div class="input-group input-group-quantity">
                        <button type="button" class="btn btn-outline-secondary" id="qty-minus">−</button>
                        <input type="number" id="count" name="count" required min="1" max="1000" value="50"
                               class="form-control">
                        <button type="button" class="btn btn-outline-secondary" id="qty-plus">+</button>
                    </div>
                    <p class="form-text text-muted mt-1 small">Max 50 for free trial. Above requires payment. <strong>$<span id="form-price-per-link">{{ number_format($pricePerLink ?? 0.01, 2) }}</span> per link</strong></p>
                </div>

                <button type="submit" id="generate-btn" class="btn btn-primary btn-lg w-100 mb-4">
                    Generate Links
                </button>
            </form>

            <div class="plan-status" id="plan-status">
                <p class="mb-1 fw-medium" id="plan-text">
                    @if ($planName ?? null)
                        Plan: {{ $planName }} —
                        @if (($planLimit ?? 0) > 0)
                            {{ $planUsed ?? 0 }} / {{ $planLimit }} used
                        @else
                            Unlimited
                        @endif
                    @else
                        Free trial (remaining: <span id="remaining">{{ $remaining ?? 50 }}</span> / 50)
                    @endif
                </p>
                @if (($planLimit ?? 50) > 0)
                <div class="progress-bar-custom mb-1">
                    <div class="progress-fill" id="progress-fill" style="width: {{ $planName ? min(100, (($planUsed ?? 0) / max(1, $planLimit ?? 1)) * 100) : (($remaining ?? 50) / 50) * 100 }}%;"></div>
                </div>
                @endif
                <a href="#" class="text-decoration-none" style="color: var(--brand);" data-bs-toggle="modal" data-bs-target="#pricingModal">View pricing</a>
            </div>
        </div>

        <div id="links-section" class="links-box mb-4" style="display: none;">
            <div class="d-flex align-items-center gap-2 mb-3">
                <input type="radio" checked class="form-check-input" id="plan-radio">
                <label for="plan-radio" class="form-check-label fw-medium mb-0" id="links-label">
                    @if ($planName ?? null)
                        Plan: {{ $planName }} ({{ $planUsed ?? 0 }}{{ ($planLimit ?? 0) > 0 ? ' / ' . $planLimit : '' }})
                    @else
                        Free trial (remaining: <span id="remaining-2">{{ $remaining ?? 0 }}</span> / 50)
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
                planText.innerHTML = 'Plan: ' + escapeHtml(planName) + ' — ' + usedText;
                linksLabel.innerHTML = 'Plan: ' + escapeHtml(planName) + ' (' + planUsed + (planLimit > 0 ? ' / ' + planLimit : '') + ')';
            } else {
                planText.innerHTML = 'Free trial (remaining: <span id="remaining">' + remaining + '</span> / 50)';
                linksLabel.innerHTML = 'Free trial (remaining: <span id="remaining-2">' + remaining + '</span> / 50)';
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
