<?php

namespace App\Services;

class PayoutRouteResolver
{
    /**
     * Get the default payout route (currency, network) for a provider.
     * Used when resolving which route to use for commission recording.
     */
    public function getDefaultRoute(string $provider): array
    {
        $routes = config('partner.default_payout_route', []);
        $key = strtolower(trim($provider));
        $route = $routes[$key] ?? ['currency' => 'USDT', 'network' => 'TRC20'];

        return [
            'currency' => $route['currency'] ?? 'USDT',
            'network' => $route['network'] ?? 'TRC20',
        ];
    }

    /**
     * Get all allowed payout routes for a provider.
     */
    public function getAllowedRoutes(string $provider): array
    {
        $routes = config('partner.allowed_payout_routes', []);
        $key = strtolower(trim($provider));

        return $routes[$key] ?? [];
    }

    /**
     * Check if a route (currency, network) is allowed for the provider.
     */
    public function isRouteAllowed(string $provider, string $currency, string $network): bool
    {
        $allowed = $this->getAllowedRoutes($provider);
        foreach ($allowed as $route) {
            if (
                strcasecmp($route['currency'] ?? '', $currency) === 0
                && strcasecmp($route['network'] ?? '', $network) === 0
            ) {
                return true;
            }
        }

        return false;
    }
}
