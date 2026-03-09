<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login – Shortlink</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --brand: #6366f1;
            --brand-hover: #4f46e5;
            --accent: #8b5cf6;
            --radius: 14px;
            --radius-sm: 10px;
        }
        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 40%, #334155 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .auth-card {
            background: rgba(255,255,255,0.97);
            backdrop-filter: blur(20px);
            border-radius: var(--radius);
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35), 0 0 0 1px rgba(255,255,255,0.1);
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
        }
        .auth-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        .auth-sub {
            color: #64748b;
            font-size: 0.9375rem;
            margin-bottom: 1.5rem;
        }
        .form-control {
            border-radius: var(--radius-sm);
            border: 1px solid #e2e8f0;
            padding: 12px 16px;
            font-size: 1rem;
        }
        .form-control:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--brand) 0%, var(--accent) 100%);
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            padding: 12px 20px;
            width: 100%;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
            transition: transform .15s, box-shadow .15s;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--brand-hover) 0%, #7c3aed 100%);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45);
            transform: translateY(-1px);
        }
        .divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.25rem 0;
            color: #94a3b8;
            font-size: 0.8125rem;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }
        .btn-social {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 12px 20px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            border: 1px solid #e2e8f0;
            background: #fff;
            transition: all .15s;
        }
        .btn-social:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }
        .btn-google { color: #1f2937; }
        .btn-telegram { color: #0088cc; }
        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9375rem;
            color: #64748b;
        }
        .auth-footer a {
            color: var(--brand);
            font-weight: 500;
            text-decoration: none;
        }
        .auth-footer a:hover { text-decoration: underline; }
        .logo-link {
            color: var(--brand) !important;
            font-weight: 600;
            font-size: 0.9375rem;
            text-decoration: none !important;
        }
        .logo-link:hover { color: var(--brand-hover) !important; }
    </style>
</head>
<body>
    <div class="auth-card">
        <a href="{{ route('shortlink.index') }}" class="logo-link d-inline-block mb-4">← Shortlink</a>
        <h1 class="auth-title">Welcome back</h1>
        <p class="auth-sub">Sign in to your account</p>

        @if ($errors->any())
            <div class="alert alert-danger py-2 mb-3">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label fw-medium">Email</label>
                <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label fw-medium">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="remember" id="remember" class="form-check-input">
                <label for="remember" class="form-check-label text-muted small">Remember me</label>
            </div>
            <button type="submit" class="btn btn-primary mb-3">Sign in</button>
        </form>

        <div class="divider">or continue with</div>

        <div class="d-flex flex-column gap-2">
            <a href="{{ route('auth.google') }}" class="btn btn-social btn-google">
                <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                Continue with Google
            </a>

            @if(config('services.telegram.bot_username'))
            <div class="d-flex justify-content-center">
                <script async src="https://telegram.org/js/telegram-widget.js?22" data-telegram-login="{{ config('services.telegram.bot_username') }}" data-size="large" data-onauth="onTelegramAuth(user)" data-request-access="write"></script>
            </div>
            @else
            <a href="#" class="btn btn-social btn-telegram" disabled title="Set TELEGRAM_BOT_USERNAME in .env">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                Continue with Telegram
            </a>
            @endif
        </div>

        <p class="auth-footer mt-3">
            Don't have an account? <a href="{{ route('auth.register') }}">Sign up</a>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @if(config('services.telegram.bot_username'))
    <script async src="https://telegram.org/js/telegram-widget.js?22" data-telegram-login="{{ config('services.telegram.bot_username') }}" data-size="large" data-onauth="onTelegramAuth(user)" data-request-access="write"></script>
    @endif
    <script>
    function onTelegramAuth(user) {
        const params = new URLSearchParams(user);
        window.location.href = '{{ route("auth.telegram") }}?' + params.toString();
    }
    </script>
</body>
</html>
