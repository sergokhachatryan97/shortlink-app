@extends('layouts.app')

@section('title', 'API Docs')

@section('content')
<div class="cosmic-page-section">
    <div class="container cosmic-container" style="max-width: 980px;">
        <div class="cosmic-page-header mb-4">
            <h1 class="cosmic-page-title">Developer API</h1>
        </div>

        @if(session('success'))
            <div class="cosmic-alert cosmic-alert-success mb-4">{{ session('success') }}</div>
        @endif

        <div class="cosmic-card p-4 mb-4">
            <h5 class="cosmic-card-title mb-3">Your API credentials</h5>
            <p class="cosmic-text-muted small mb-2">API URL</p>
            <div class="d-flex gap-2 mb-3">
                <input id="api-url-field" class="cosmic-input form-control" readonly value="{{ $endpointUrl }}">
                <button type="button" class="btn cosmic-btn-copy" data-copy-target="api-url-field">Copy</button>
            </div>

            <p class="cosmic-text-muted small mb-2">API key</p>
            @if($newApiKey)
                <div class="cosmic-alert cosmic-alert-warn mb-3">
                    Copy your new API key now. For security reasons it is shown only once.
                </div>
                <div class="d-flex gap-2 mb-3">
                    <input id="api-key-field" class="cosmic-input form-control" readonly value="{{ $newApiKey }}">
                    <button type="button" class="btn cosmic-btn-copy" data-copy-target="api-key-field">Copy</button>
                </div>
            @elseif(!empty($currentApiKey))
                <div class="d-flex gap-2 mb-2">
                    <input id="api-key-field" class="cosmic-input form-control" readonly value="{{ $currentApiKey }}">
                    <button type="button" class="btn cosmic-btn-copy" data-copy-target="api-key-field">Copy</button>
                </div>
                <p class="cosmic-text-muted small mb-3">Keep this key private. Anyone with this key can use your API balance.</p>
            @elseif($client)
                <div class="cosmic-alert cosmic-alert-info mb-3">Your old API key was created before secure key display support. Please regenerate once to enable show/copy.</div>
            @else
                <div class="cosmic-alert cosmic-alert-info mb-3">You do not have an API key yet. Generate one now.</div>
            @endif

            <form method="POST" action="{{ route('api.docs.regenerate') }}">
                @csrf
                <button type="submit" class="btn cosmic-btn-primary">Regenerate API Key</button>
            </form>
        </div>

        <div class="cosmic-card p-4">
            <h5 class="cosmic-card-title mb-3">API documentation</h5>
            <p class="cosmic-text-muted small mb-3">Endpoint accepts <code>application/x-www-form-urlencoded</code> GET requests.</p>

            <pre class="cosmic-pre mb-3">GET {{ $endpointUrl }}
key=YOUR_API_KEY&amp;action=services</pre>

            <h6 class="cosmic-subtitle mt-3">Actions</h6>
            <ul class="cosmic-list small mb-3">
                <li><code>services</code> - List active services</li>
                <li><code>balance</code> - Get your balance</li>
                <li><code>add</code> - Create an order and return generated links when ready</li>
{{--                <li><code>status</code> - Get single order status + generated links (when completed)</li>--}}
{{--                <li><code>multiple_status</code> - Get multiple order statuses</li>--}}
{{--                <li><code>cancel</code> - Cancel order (if allowed)</li>--}}
            </ul>

            <h6 class="cosmic-subtitle">Example requests</h6>
            <pre class="cosmic-pre">key=YOUR_API_KEY&amp;action=balance
key=YOUR_API_KEY&amp;action=services
key=YOUR_API_KEY&amp;action=add&amp;service=1&amp;link=https://t.me/example&amp;quantity=500
{{--key=YOUR_API_KEY&amp;action=status&amp;order=1521--}}
{{--key=YOUR_API_KEY&amp;action=multiple_status&amp;orders=1521,1522,1523--}}
{{--key=YOUR_API_KEY&amp;action=cancel&amp;order=1521--}}
            </pre>

            <h6 class="cosmic-subtitle mt-3">Response examples</h6>
            <pre class="cosmic-pre mb-0">{"balance":"125.40","currency":"USD"}

{"order":1521,"status":"Completed","original_link":"https://example.com/page","quantity":3,"charge":"0.03","currency":"USD","generated_links":["https://trastly.org/abc123","https://trastly.org/def456","https://trastly.org/ghi789"]}

{"charge":"0.03","status":"Completed","currency":"USD","original_link":"https://example.com/page","quantity":3,"generated_links":["https://trastly.org/abc123","https://trastly.org/def456","https://trastly.org/ghi789"]}

{"error":"Invalid API key"}</pre>
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
.cosmic-page-subtitle { color: rgba(255,255,255,0.7); font-size: 0.95rem; }
.cosmic-text-muted { color: rgba(255,255,255,0.65); }
.cosmic-card { background: rgba(30, 30, 45, 0.72); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.3); }
.cosmic-card-title { color: #fff; font-weight: 600; }
.cosmic-subtitle { color: #e5e7eb; font-weight: 600; }
.cosmic-input { background: rgba(30,30,45,0.8) !important; border: 1px solid rgba(167,139,250,0.3) !important; color: #fff !important; border-radius: 10px; }
.cosmic-input::placeholder { color: rgba(255,255,255,0.55) !important; opacity: 1; }
.cosmic-btn-primary { background: linear-gradient(135deg, #6366f1, #8b5cf6); border: none; color: #fff !important; font-weight: 600; padding: 10px 24px; border-radius: 10px; }
.cosmic-btn-copy { background: rgba(30,30,45,0.9); border: 1px solid rgba(255,255,255,0.2); color: #fff; border-radius: 8px; padding: 6px 14px; white-space: nowrap; }
.cosmic-btn-copy:hover:not(:disabled) { background: rgba(40,40,60,0.9); color: #fff; }
.cosmic-btn-copy:disabled { opacity: 0.55; cursor: not-allowed; }
.cosmic-alert { border-radius: 12px; padding: 0.85rem 1rem; }
.cosmic-alert-success { background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.4); color: #86efac; }
.cosmic-alert-info { background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.4); color: #93c5fd; }
.cosmic-alert-warn { background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.4); color: #fcd34d; }
.cosmic-pre { background: rgba(17,24,39,0.75); border: 1px solid rgba(99,102,241,0.25); color: #e5e7eb; border-radius: 10px; padding: 0.85rem 1rem; font-size: 0.83rem; white-space: pre-wrap; }
.cosmic-list { color: rgba(255,255,255,0.86); }
.cosmic-list li { margin-bottom: 0.2rem; }
</style>
@endpush

@push('scripts')
<script>
document.querySelectorAll('.cosmic-btn-copy[data-copy-target]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const targetId = btn.getAttribute('data-copy-target');
        const input = document.getElementById(targetId);
        if (!input) return;
        const original = btn.textContent;
        navigator.clipboard.writeText(input.value).then(function() {
            btn.textContent = 'Copied!';
            setTimeout(function() { btn.textContent = original; }, 1200);
        });
    });
});
</script>
@endpush
