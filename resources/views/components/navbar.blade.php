<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom fixed-top" style="box-shadow: 0 1px 2px rgba(0,0,0,.05);">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 p-0" href="{{ route('shortlink.index') }}">
            <img src="{{ asset('brand/trastly-star-icon.svg') }}" alt="" class="navbar-star-logo" height="32" width="32">
            <span class="navbar-brand-text">Trastly</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto align-items-center gap-1">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('shortlink.index') ? 'active fw-600' : '' }}" href="{{ route('shortlink.index') }}">Generator</a>
                </li>
                @auth
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('links.*') ? 'active fw-600' : '' }}" href="{{ route('links.index') }}">My Links</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('subscription.index') ? 'active fw-600' : '' }}" href="{{ route('subscription.index') }}">Pricing</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('profile.*') ? 'active fw-600' : '' }}" href="{{ route('profile.index') }}">Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('contact.index') ? 'active fw-600' : '' }}" href="{{ route('contact.index') }}">Contact</a>
                </li>
                @endauth
                @guest
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('subscription.index') ? 'active fw-600' : '' }}" href="{{ route('subscription.index') }}">Pricing</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('contact.index') ? 'active fw-600' : '' }}" href="{{ route('contact.index') }}">Contact</a>
                </li>
                @endguest
            </ul>
            <ul class="navbar-nav align-items-center gap-2 navbar-wallet-logout">
                @auth
                <li class="nav-item dropdown">
                    <a class="nav-wallet dropdown-toggle d-flex align-items-center gap-2 text-decoration-none" href="#" role="button" data-bs-toggle="dropdown">
                        <span class="nav-wallet-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                        <span class="nav-wallet-text">Wallet <span class="balance-amount" id="balance-amount">${{ number_format(auth()->user()->balance ?? 0, 2) }}</span></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="border-radius: 10px;">
                        <li><a class="dropdown-item" href="{{ route('balance.index') }}">Add funds</a></li>
                        <li><a class="dropdown-item" href="{{ route('subscription.index') }}">Subscription</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <form action="{{ route('auth.logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="nav-logout-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <span>Logout</span>
                        </button>
                    </form>
                </li>
                @else
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('auth.login') }}">Sign in</a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-sm text-white" href="{{ route('auth.register') }}" style="background:linear-gradient(135deg,#6366f1,#8b5cf6); border:none; font-weight:600; padding:6px 14px; border-radius:8px;">Sign up</a>
                </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
<style>
.navbar .nav-link { color:#64748b !important; font-weight:500; }
.navbar .nav-link:hover { color:var(--brand) !important; }
.navbar .nav-link.active { color:var(--brand) !important; }
.navbar { z-index: 1030; }
.navbar-star-logo { flex-shrink: 0; object-fit: contain; }
.navbar-brand-text { font-weight: 700;}
.cosmic-page-body .navbar-brand-text,
.auth-page-body .navbar-brand-text { color: #a78bfa; }
/* Wallet & Logout - glassmorphism style */
.navbar-wallet-logout { gap: 0.75rem !important; }
.nav-wallet {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 12px;
    background: rgba(30,30,45,0.6);
    border: 1px solid rgba(167,139,250,0.3);
    box-shadow: 0 0 16px rgba(139,92,246,0.15);
    color: rgba(255,255,255,0.95);
    font-weight: 600;
    font-size: 0.9375rem;
}
.nav-wallet:hover { color: #fff; background: rgba(40,40,60,0.7); border-color: rgba(167,139,250,0.4); }
.nav-wallet-icon { color: #a78bfa; flex-shrink: 0; display: flex; }
.nav-wallet::after { border-top-color: currentColor; margin-left: 0.25rem; }
.nav-logout-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 12px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border: none;
    color: #fff !important;
    font-weight: 600;
    font-size: 0.9375rem;
    box-shadow: 0 0 16px rgba(139,92,246,0.25);
    cursor: pointer;
}
.nav-logout-btn:hover { opacity: 0.95; color: #fff !important; background: linear-gradient(135deg, #5558e3, #7c3aed); }
/* Light theme (landing) override for wallet */
body:not(.cosmic-page-body) .nav-wallet {
    background: rgba(241,245,249,0.9);
    border-color: rgba(99,102,241,0.2);
    color: #0f172a;
}
body:not(.cosmic-page-body) .nav-wallet:hover { background: rgba(226,232,240,0.95); }
body:not(.cosmic-page-body) .nav-wallet-icon { color: #6366f1; }
body:not(.cosmic-page-body):not(.auth-page-body):not(.landing-page) .navbar .dropdown-menu { background: #fff !important; border: 1px solid rgba(0,0,0,0.1) !important; }
body:not(.cosmic-page-body):not(.auth-page-body):not(.landing-page) .navbar .dropdown-item { color: #1e293b !important; }
body:not(.cosmic-page-body):not(.auth-page-body):not(.landing-page) .navbar .dropdown-item:hover { background: #f8fafc !important; color: #0f172a !important; }
/* Cosmic dark theme for dashboard pages */
.cosmic-page-body { background: #0a0a12 !important; }
.cosmic-page-body .navbar { background: rgba(30,30,45,0.95) !important; border-color: rgba(255,255,255,0.06) !important; box-shadow: none !important; }
.cosmic-page-body .navbar .navbar-brand { color: #a78bfa !important; }
.cosmic-page-body .navbar .nav-link { color: rgba(255,255,255,0.8) !important; }
.cosmic-page-body .navbar .nav-link:hover { color: #fff !important; }
.cosmic-page-body .navbar .nav-link.active { color: #a78bfa !important; }
.cosmic-page-body .navbar .navbar-toggler { border-color: rgba(255,255,255,0.3); }
.cosmic-page-body .navbar .navbar-toggler-icon { filter: invert(1); }
.cosmic-page-body .navbar .dropdown-menu { background: rgba(30,30,45,0.95); border: 1px solid rgba(255,255,255,0.1); }
.cosmic-page-body .navbar .dropdown-item { color: rgba(255,255,255,0.9); }
.cosmic-page-body .navbar .dropdown-item:hover { background: rgba(255,255,255,0.1); color: #fff; }
.cosmic-page-body .footer-contact { background: rgba(10,10,18,0.8); border-color: rgba(255,255,255,0.08) !important; }
.cosmic-page-body .footer-contact .text-muted { color: rgba(255,255,255,0.5) !important; }
.cosmic-page-body .footer-contact a:not(.text-muted) { color: #a78bfa !important; }
/* Auth pages (login/register) - dark navbar */
.auth-page-body .navbar { background: rgba(30,30,45,0.95) !important; border-color: rgba(255,255,255,0.06) !important; box-shadow: none !important; }
.auth-page-body .navbar .navbar-brand { color: #a78bfa !important; }
.auth-page-body .navbar .nav-link { color: rgba(255,255,255,0.8) !important; }
.auth-page-body .navbar .nav-link:hover { color: #fff !important; }
.auth-page-body .navbar .nav-link.active { color: #a78bfa !important; }
.auth-page-body .navbar .navbar-toggler { border-color: rgba(255,255,255,0.3); }
.auth-page-body .navbar .navbar-toggler-icon { filter: invert(1); }
.auth-page-body .navbar .dropdown-menu { background: rgba(30,30,45,0.95); border: 1px solid rgba(255,255,255,0.1); }
.auth-page-body .navbar .dropdown-item { color: rgba(255,255,255,0.9); }
.auth-page-body .navbar .dropdown-item:hover { background: rgba(255,255,255,0.1); color: #fff; }
</style>
