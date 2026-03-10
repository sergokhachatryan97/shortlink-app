@extends('layouts.app')

@section('title', 'Contact')

@section('content')
<div class="cosmic-page-section">
    <div class="container cosmic-container" style="max-width: 600px;">
        <div class="cosmic-page-header mb-4">
            <h1 class="cosmic-page-title">Contact</h1>
            <p class="cosmic-page-subtitle mb-0">Get in touch with our support team.</p>
        </div>

        <div class="cosmic-card p-4">
            <h5 class="cosmic-card-title d-flex align-items-center gap-2 mb-3">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                Contact Support
            </h5>
            <p class="cosmic-text-muted mb-2">For any questions or assistance, please contact us:</p>
            <p class="mb-2">
                <a href="mailto:{{ $supportEmail }}" class="cosmic-email d-inline-flex align-items-center gap-2">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    {{ $supportEmail }}
                </a>
            </p>
            @if(config('app.support_telegram'))
            <p class="mb-0">
                <a href="{{ config('app.support_telegram') }}" target="_blank" rel="noopener" class="cosmic-email d-inline-flex align-items-center gap-2">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                    Telegram
                </a>
            </p>
            @endif
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
.cosmic-text-muted { color: rgba(255,255,255,0.7); }
.cosmic-email { color: #a78bfa; font-weight: 600; text-decoration: none; font-size: 1.1rem; }
.cosmic-email:hover { color: #c4b5fd; }
</style>
@endpush
@endsection
