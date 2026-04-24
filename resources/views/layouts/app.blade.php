<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @stack('head')
    <title>@yield('title', 'Shortlink')</title>
    @if (config('services.yandex_metrika.id'))
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){
            m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
            m[i].l=1*new Date();
            for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
            k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
        })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id={{ config('services.yandex_metrika.id') }}', 'ym');

        ym({{ (int) config('services.yandex_metrika.id') }}, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/{{ config('services.yandex_metrika.id') }}" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <!-- /Yandex.Metrika counter -->
    @endif
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
<body class="{{ request()->routeIs('links.*') || request()->routeIs('contact.index') || request()->routeIs('subscription.index') || request()->routeIs('profile.*') || request()->routeIs('balance.*') || request()->routeIs('partner.*') || request()->routeIs('api.docs*') || request()->routeIs('shortlink.payment') ? 'cosmic-page-body' : '' }}">
    @include('components.navbar')
    <main class="main-content d-flex flex-column" style="min-height: calc(100vh - var(--navbar-height) - 80px);">
        @yield('content')
    </main>
    @include('components.footer')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
