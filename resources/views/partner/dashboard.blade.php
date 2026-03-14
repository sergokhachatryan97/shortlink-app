@extends('layouts.app')

@section('title', 'Partner Dashboard')

@section('content')
<div class="cosmic-page-section">
    <div class="container cosmic-container" style="max-width: 720px;">
        <div class="cosmic-page-header mb-4">
            <h1 class="cosmic-page-title">Partner Dashboard</h1>
            <p class="cosmic-page-subtitle mb-0">Share your referral link and earn {{ number_format($commissionPercent ?? 10, 1) }}% commission when referred users pay.</p>
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
                <p class="cosmic-text-muted mb-4">Activate partner mode to get your unique referral link and start earning commissions when people you refer make payments.</p>
                <form method="POST" action="{{ route('partner.activate') }}">
                    @csrf
                    <button type="submit" class="btn cosmic-btn-primary">Become a Partner</button>
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
                <h5 class="cosmic-card-title mb-3">Stats</h5>
                <p class="cosmic-text-muted mb-1">Referred users: <strong>{{ $referredCount }}</strong></p>
                <p class="cosmic-text-muted mb-0">Your commission rate: <strong>{{ number_format($commissionPercent ?? 10, 1) }}%</strong>@if($user->commission_percent !== null) <span class="badge bg-info ms-1">Custom</span>@endif</p>
            </div>

            <div class="cosmic-card p-4 mb-4">
                <h5 class="cosmic-card-title mb-3">Payout Settings</h5>
                <p class="cosmic-text-muted small mb-4">Configure your wallet to receive commissions. The payout system (Heleket/CoinRush) is set by admin. Add the wallet for the provider admin has configured for you.</p>

                @php
                    $enabledProviders = config('partner.payout_providers_enabled', ['heleket']);
                    $providerConfigs = [
                        'heleket' => ['Heleket', in_array('heleket', $enabledProviders)],
                        'coinrush' => ['CoinRush', in_array('coinrush', $enabledProviders)],
                    ];
                @endphp
                @foreach($providerConfigs as $provider => $providerData)
                @php
                    $label = $providerData[0];
                    $enabled = $providerData[1];
                    $setting = $payoutSettings->firstWhere('provider', $provider);
                @endphp
                <div class="cosmic-payout-setting mb-4 pb-4 {{ !$loop->last ? 'border-bottom border-secondary' : '' }}">
                    <h6 class="cosmic-card-title small mb-2">{{ $label }}@if(!$enabled) <span class="badge bg-secondary ms-1">Coming soon</span>@endif</h6>
                    <form method="POST" action="{{ route('partner.payout-settings.update') }}">
                        @csrf
                        <input type="hidden" name="provider" value="{{ $provider }}">
                        <div class="mb-2">
                            <input type="text" name="wallet_address" class="cosmic-input form-control" placeholder="Wallet address" value="{{ $setting?->wallet_address }}">
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="active-{{ $provider }}" {{ ($setting?->is_active ?? false) && $setting?->wallet_address ? 'checked' : '' }}>
                                <label class="form-check-label cosmic-text-muted small" for="active-{{ $provider }}">Active</label>
                            </div>
                            <button type="submit" class="btn btn-sm cosmic-btn-primary">Save</button>
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
        navigator.clipboard.writeText(text).then(function() {
            const orig = btn.textContent;
            btn.textContent = 'Copied!';
            setTimeout(function() { btn.textContent = orig; }, 1500);
        });
    });
});
</script>
@endpush
