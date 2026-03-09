<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom fixed-top" style="box-shadow: 0 1px 2px rgba(0,0,0,.05);">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('shortlink.index') }}" style="color:#0f172a;">Shortlink</a>
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
                    <a class="nav-link {{ request()->routeIs('subscription.*') ? 'active fw-600' : '' }}" href="{{ route('subscription.index') }}">Billing</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('profile.*') ? 'active fw-600' : '' }}" href="{{ route('profile.index') }}">Profile</a>
                </li>
                @endauth
            </ul>
            <ul class="navbar-nav align-items-center gap-2">
                @auth
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 px-3 py-2 rounded-3 text-decoration-none" href="#" role="button" data-bs-toggle="dropdown" style="background:#f1f5f9; color:#0f172a; font-weight: 600;">
                        <span style="font-size:0.75rem; color:#64748b; font-weight:500;">BALANCE</span>
                        <span class="balance-amount" id="balance-amount">${{ number_format(auth()->user()->balance ?? 0, 2) }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="border-radius: 10px;">
                        <li><a class="dropdown-item" href="{{ route('balance.index') }}">Add funds</a></li>
                        <li><a class="dropdown-item" href="{{ route('subscription.index') }}">Subscription</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a href="{{ route('balance.index') }}" class="btn btn-sm text-white" style="background:linear-gradient(135deg,#6366f1,#8b5cf6); border:none; font-weight:600; padding:6px 12px; border-radius:8px;">+</a>
                </li>
                <li class="nav-item">
                    <form action="{{ route('auth.logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Logout</button>
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
</style>
