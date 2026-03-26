<?php

namespace App\Http\Middleware;

use App\Actions\PanelApi\AuthenticateExternalPanelClient;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExternalPanelAuth
{
    public function __construct(
        protected AuthenticateExternalPanelClient $authenticator
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $client = $this->authenticator->execute($request);
        if (!$client) {
            return new JsonResponse(['error' => 'Invalid API key'], 401);
        }

        if (!empty($client->allowed_ips) && !in_array($request->ip(), $client->allowed_ips, true)) {
            return new JsonResponse(['error' => 'IP not allowed'], 403);
        }

        $request->attributes->set('externalClient', $client);

        return $next($request);
    }
}
