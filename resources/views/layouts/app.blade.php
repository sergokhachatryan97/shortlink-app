<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Shortlink')</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root{--brand:#6366f1;--accent:#8b5cf6;--navbar-height: 72px;}
        @media (max-width: 991.98px) { :root{--navbar-height: 80px;} }
        body{font-family:'DM Sans',sans-serif;background:linear-gradient(135deg,#f8fafc 0%,#f1f5f9 50%,#e2e8f0 100%);min-height:100vh;padding-top:var(--navbar-height)!important}
        .navbar-brand{font-weight:700;color:#0f172a!important}
        .nav-link{font-weight:500;color:#475569!important}
        .nav-link:hover{color:var(--brand)!important}
        .btn-nav{background:linear-gradient(135deg,var(--brand),var(--accent));color:#fff!important;font-weight:600;border:none}
        .main-content{padding:2rem 0 3rem}
    </style>
    @stack('styles')
</head>
<body>
    @include('components.navbar')
    <main class="main-content">
        @yield('content')
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
