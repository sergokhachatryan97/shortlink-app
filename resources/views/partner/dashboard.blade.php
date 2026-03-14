@extends('layouts.app')

@section('title', __('messages.partner.dashboard'))

@section('content')
<div class="cosmic-page-section">
    <div class="container cosmic-container" style="max-width: 720px;">
        <div class="cosmic-page-header mb-4">
            <h1 class="cosmic-page-title">Partner Dashboard</h1>
            <p class="cosmic-page-subtitle mb-0">{{ __('messages.partner.subtitle', ['percent' => number_format($commissionPercent ?? 10, 1)]) }}</p>
        </div>

        @if(session('success'))
            <div class="cosmic-alert cosmic-alert-success mb-4">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="cosmic-alert cosmic-alert-danger mb-4">{{ session('error') }}</div>
        @endif

        @if (!$user->is_partner)
            <div class="cosmic-card p-4 mb-4">
                <h5 class="cosmic-card-title mb-3">Become a Partner</h5>
                <p class="cosmic-text-muted mb-4">Activate partner mode to get your unique referral link and earn commissions in USDT (TRC20) when people you refer make payments.</p>
                <form method="POST" action="{{ route('partner.activate') }}">
                    @csrf
                    <button type="submit" class="btn cosmic-btn-primary">{{ __('messages.partner.activate') }}</button>
                </form>
            </div>
        @else
            <div class="cosmic-card p-4 mb-4">
                <h5 class="cosmic-card-title mb-3">Your Referral Link</h5>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <input type="text" class="cosmic-input form-control flex-grow-1" value="{{ $referralLink }}" readonly id="referral-link">
                    <button type="button" class="btn cosmic-btn-copy" data-copy="{{ $referralLink }}">Copy</button>
                </div>
            </div>

            <div class="cosmic-card p-4 mb-4">
                <h5 class="cosmic-card-title mb-3">{{ __('messages.partner.stats') }}</h5>
                <p class="cosmic-text-muted mb-1">{{ __('messages.partner.referred_users') }} <strong>{{ $referredCount }}</strong></p>
                <p class="cosmic-text-muted mb-2">{{ __('messages.partner.commission_rate') }} <strong>{{ number_format($commissionPercent ?? 10, 1) }}%</strong>@if($user->commission_percent !== null) <span class="badge bg-info ms-1">{{ __('messages.partner.custom') }}</span>@endif</p>
                <p class="cosmic-text-muted small mb-0">{{ __('messages.partner.commission_note') }}</p>
            </div>

            <div class="cosmic-card p-4 mb-4">
                <h5 class="cosmic-card-title mb-3">Payout wallet (USDT)</h5>
                <p class="cosmic-text-muted small mb-4">Enter a valid USDT (TRC20) address. Must start with T and be 34 characters. Invalid addresses are rejected. Payouts run when total reaches the minimum (set by admin).</p>

                @php
                    $allowedRoutes = config('partner.allowed_payout_routes.heleket', []);
                @endphp
                @foreach($allowedRoutes as $route)
                @php
                    $currency = $route['currency'] ?? 'USDT';
                    $network = $route['network'] ?? 'TRC20';
                    $routeLabel = $route['label'] ?? $currency . ' (' . $network . ')';
                    $routeKey = 'heleket-' . $currency . '-' . $network;
                    $setting = $payoutSettings->first(fn ($s) => $s->provider === 'heleket' && ($s->currency ?? '') === $currency && ($s->network ?? '') === $network);
                @endphp
                <div class="cosmic-payout-setting mb-4 pb-4 {{ !$loop->last ? 'border-bottom border-secondary' : '' }}">
                    <h6 class="cosmic-card-title small mb-2">{{ __('messages.partner.usdt_via_heleket') }}</h6>
                    <form method="POST" action="{{ route('partner.payout-settings.update') }}">
                        @csrf
                        <input type="hidden" name="provider" value="heleket">
                        <input type="hidden" name="currency" value="{{ $currency }}">
                        <input type="hidden" name="network" value="{{ $network }}">
                        <div class="mb-2">
                            <label class="form-label small cosmic-text-muted mb-1">Valid USDT (TRC20) address</label>
                            <input type="text" name="wallet_address" class="cosmic-input form-control" placeholder="T..." pattern="^T[a-zA-Z0-9]{33}$" maxlength="34" value="{{ $setting?->wallet_address }}" title="USDT TRC20 address: starts with T, 34 characters">
                        </div>
                        @if($errors->any())
                            <div class="cosmic-alert cosmic-alert-danger mb-2 small">{{ $errors->first() }}</div>
                        @endif
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="active-{{ $routeKey }}" {{ ($setting?->is_active ?? false) && $setting?->wallet_address ? 'checked' : '' }}>
                                <label class="form-check-label cosmic-text-muted small" for="active-{{ $routeKey }}">{{ __('messages.partner.active') }}</label>
                            </div>
                            <button type="submit" class="btn btn-sm cosmic-btn-primary">{{ __('messages.partner.save') }}</button>
                        </div>
                    </form>
                </div>
                @endforeach
            </div>
        @endif
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
.cosmic-text-muted { color: rgba(255,255,255,0.65); }
.cosmic-card { background: rgba(30, 30, 45, 0.7); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.3); }
.cosmic-card-title { color: #fff; font-weight: 600; }
.cosmic-input { background: rgba(30,30,45,0.8) !important; border: 1px solid rgba(167,139,250,0.3) !important; color: #fff !important; border-radius: 10px; }
.cosmic-btn-primary { background: linear-gradient(135deg, #6366f1, #8b5cf6); border: none; color: #fff !important; font-weight: 600; padding: 10px 24px; border-radius: 10px; }
.cosmic-btn-copy { background: rgba(30,30,45,0.9); border: 1px solid rgba(255,255,255,0.2); color: #fff; border-radius: 8px; padding: 6px 14px; }
.cosmic-btn-copy:hover { background: rgba(40,40,60,0.9); color: #fff; }
.cosmic-alert { border-radius: 12px; padding: 1rem 1.25rem; }
.cosmic-alert-success { background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.4); color: #86efac; }
.cosmic-alert-danger { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.4); color: #fca5a5; }
.border-secondary { border-color: rgba(255,255,255,0.1) !important; }
</style>
@endpush

@push('scripts')
<script>
document.querySelectorAll('.cosmic-btn-copy').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const text = this.dataset.copy;
        const orig = btn.textContent;
        navigator.clipboard.writeText(text).then(function() {
            btn.textContent = @json(__('messages.common.copied'));
            setTimeout(function() { btn.textContent = orig; }, 1500);
        });
    });
});
</script>
@endpush
