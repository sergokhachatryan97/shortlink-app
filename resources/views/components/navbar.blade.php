<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm border-bottom fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('shortlink.index') }}" style="color:#0f172a">Shortlink</a>
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
                    <a class="nav-link {{ request()->routeIs('subscription.*') ? 'active fw-600' : '' }}" href="{{ route('subscription.index') }}">Subscription</a>
                </li>
                @endauth
            </ul>
            <ul class="navbar-nav align-items-center gap-2">
                @auth
                <li class="nav-item">
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('balance.index') }}" class="balance-add-btn" title="Add funds">+</a>
                        <a href="{{ route('balance.index') }}" class="balance-widget">
                            <span class="balance-label">BALANCE:</span>
                            <span class="balance-amount">${{ number_format(auth()->user()->balance ?? 0, 2) }}</span>
                        </a>
                    </div>
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
                    <a class="btn btn-sm text-white" href="{{ route('auth.register') }}" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);font-weight:600;border:none">Sign up</a>
                </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
<style>
.navbar .nav-link { color:#475569!important; font-weight:500 }
.navbar .nav-link:hover { color:#6366f1!important }
.navbar .nav-link.active { color:#6366f1!important }
.balance-add-btn {
    display:flex;align-items:center;justify-content:center;
    width:36px;height:36px;border-radius:50%;
    background:linear-gradient(135deg,#6366f1,#8b5cf6);
    color:#fff!important;font-size:1.25rem;font-weight:300;line-height:1;
    text-decoration:none;box-shadow:0 2px 8px rgba(99,102,241,0.4);
}
.balance-add-btn:hover { color:#fff!important; opacity:0.9 }
.balance-widget {
    display:flex;align-items:center;gap:8px;
    padding:6px 14px;background:#f1f5f9;border-radius:10px;
    text-decoration:none;color:inherit;
}
.balance-label { font-size:0.75rem;color:#64748b;font-weight:500 }
.balance-amount { font-size:1rem;font-weight:700;color:#0f172a }
.navbar { z-index: 1030; }
</style>
