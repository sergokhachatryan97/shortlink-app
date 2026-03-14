<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    protected array $supported = ['en', 'zh', 'ru'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('admin.*')) {
            return $next($request);
        }

        $locale = $request->query('locale')
            ?? session('locale')
            ?? config('app.locale', 'en');

        if (in_array($locale, $this->supported, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
