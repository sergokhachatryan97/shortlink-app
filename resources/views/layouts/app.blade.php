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
        :root {
            --brand: #6366f1;
            --brand-hover: #4f46e5;
            --accent: #8b5cf6;
            --navbar-height: 64px;
            --card-radius: 12px;
            --card-shadow: 0 1px 3px rgba(0,0,0,.06);
            --card-shadow-lg: 0 4px 6px -1px rgba(0,0,0,.08), 0 2px 4px -2px rgba(0,0,0,.06);
        }
        @media (max-width: 991.98px) { :root { --navbar-height: 72px; } }
        body {
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            padding-top: var(--navbar-height) !important;
            color: #1e293b;
        }
        .main-content { padding: 1.5rem 0 3rem; }
        .page-header { margin-bottom: 1.5rem; }
        .page-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin-bottom: 0.25rem; }
        .page-subtitle { color: #64748b; font-size: 0.9375rem; }
        .card-dashboard {
            background: #fff;
            border: none;
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
        }
        .btn-primary-gradient {
            background: linear-gradient(135deg, var(--brand), var(--accent));
            border: none;
            font-weight: 600;
            border-radius: 10px;
        }
        .btn-primary-gradient:hover {
            background: linear-gradient(135deg, var(--brand-hover), #7c3aed);
        }
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
