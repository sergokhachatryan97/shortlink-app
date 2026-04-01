<?php

namespace App\Http\Middleware;

use App\Models\ExternalClient;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ExternalPanelRateLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var ExternalClient|null $client */
        $client = $request->attributes->get('externalClient');

        if (!$client) {
            return $next($request);
        }

        $limit = (int) ($client->rate_limit_per_minute ?? 5000);
        $limit = max(10, $limit);

        $action = (string) $request->input('action', '');
        if (in_array($action, ['cancel'], true)) {
            $limit = max(5, (int) floor($limit / 2));
        }

        $actionKey = $action !== '' ? $action : 'default';
        $key = 'panel-api:' . $client->id . ':' . $actionKey;

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return new JsonResponse([
                'error' => 'Rate limit exceeded',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
