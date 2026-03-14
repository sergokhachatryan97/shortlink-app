@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="cosmic-page-section">
    <div class="container cosmic-container" style="max-width: 600px;">
        <div class="cosmic-page-header mb-4">
            <h1 class="cosmic-page-title">Profile</h1>
            <p class="cosmic-page-subtitle mb-0">Manage your account and preferences.</p>
        </div>

        @if (session('success'))
            <div class="cosmic-alert cosmic-alert-success mb-4">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="cosmic-alert cosmic-alert-danger mb-4">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf

            <div class="cosmic-card p-4 mb-4">
                <h5 class="cosmic-card-title mb-3">Partner program</h5>
                @if (auth()->user()->is_partner ?? false)
                    <p class="cosmic-text-muted mb-2">You are a partner. Share your referral link to earn commissions.</p>
                    <a href="{{ route('partner.dashboard') }}" class="btn cosmic-btn-primary">Partner dashboard</a>
                @else
                    <p class="cosmic-text-muted mb-2">Activate partner mode to get your referral link and earn 10% when referred users pay.</p>
                    <form method="POST" action="{{ route('partner.activate') }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="redirect" value="{{ route('partner.dashboard') }}">
                        <button type="submit" class="btn cosmic-btn-primary">Become a Partner</button>
                    </form>
                @endif
            </div>

            <div class="cosmic-card p-4 mb-4">
                <h5 class="cosmic-card-title mb-3">Manage account</h5>
                <div class="cosmic-form-group">
                    <label for="name" class="cosmic-label">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', auth()->user()->name) }}" class="cosmic-input form-control" required maxlength="255">
                </div>
                @if(auth()->user()->email)
                <div class="cosmic-form-group mt-3">
                    <label class="cosmic-label">Email</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="text" class="cosmic-input form-control" value="{{ auth()->user()->email }}" disabled>
                        <button type="button" class="btn cosmic-btn-copy" data-copy="{{ auth()->user()->email }}">Copy</button>
                    </div>
                </div>
                @endif
            </div>

            <div class="cosmic-card p-4 mb-4">
                <h5 class="cosmic-card-title mb-3">Change password</h5>
                <p class="cosmic-text-muted small mb-3">Leave blank to keep your current password. {{ auth()->user()->google_id ? 'You can set a password to sign in with email.' : '' }}</p>
                @if (!auth()->user()->google_id)
                <div class="cosmic-form-group mb-3">
                    <label for="current_password" class="cosmic-label">Current password</label>
                    <input type="password" name="current_password" id="current_password" class="cosmic-input form-control" autocomplete="current-password">
                </div>
                @endif
                <div class="cosmic-form-group mb-3">
                    <label for="password" class="cosmic-label">New password</label>
                    <input type="password" name="password" id="password" class="cosmic-input form-control" autocomplete="new-password">
                </div>
                <div class="cosmic-form-group">
                    <label for="password_confirmation" class="cosmic-label">Confirm new password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="cosmic-input form-control" autocomplete="new-password">
                </div>
            </div>

            <button type="submit" class="btn cosmic-btn-primary">Save changes</button>
        </form>
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
.cosmic-label { color: rgba(255,255,255,0.8); font-size: 0.875rem; }
.cosmic-input {
    background: rgba(30,30,45,0.8) !important;
    border: 1px solid rgba(255,255,255,0.15) !important;
    color: #fff !important;
    border-radius: 10px;
}
.cosmic-input::placeholder { color: rgba(255,255,255,0.5); }
.cosmic-input:focus {
    background: rgba(30,30,45,0.9) !important;
    border-color: #a78bfa !important;
    box-shadow: 0 0 0 3px rgba(167,139,250,0.3);
    color: #fff !important;
}
.cosmic-input:disabled { opacity: 0.7; }
.cosmic-text-muted { color: rgba(255,255,255,0.65); }
.cosmic-btn-copy {
    background: rgba(30,30,45,0.8);
    border: 1px solid rgba(255,255,255,0.2);
    color: #fff;
    border-radius: 8px;
    padding: 6px 14px;
    font-size: 0.875rem;
}
.cosmic-btn-copy:hover { background: rgba(40,40,60,0.9); color: #fff; }
.cosmic-btn-primary {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border: none;
    color: #fff !important;
    font-weight: 600;
    padding: 10px 24px;
    border-radius: 10px;
}
.cosmic-btn-primary:hover { opacity: 0.95; color: #fff !important; }
.cosmic-alert {
    border-radius: 12px;
    padding: 1rem 1.25rem;
}
.cosmic-alert-success { background: rgba(34,197,94,0.2); border: 1px solid rgba(34,197,94,0.5); color: #86efac; }
.cosmic-alert-danger { background: rgba(239,68,68,0.2); border: 1px solid rgba(239,68,68,0.5); color: #fca5a5; }
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
@endsection
