<?php

namespace App\Services\PartnerPayout;

use App\Contracts\PartnerPayoutProviderInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use InvalidArgumentException;

class PartnerPayoutProviderResolver
{
    protected array $providers = [];

    public function __construct()
    {
        $this->register('heleket', HeleketPayoutProvider::class);
        $this->register('coinrush', CoinrushPayoutProvider::class);
    }

    public function register(string $key, string $class): void
    {
        $this->providers[strtolower($key)] = $class;
    }

    public function resolve(string $provider): PartnerPayoutProviderInterface
    {
        $key = strtolower($provider);
        $class = $this->providers[$key] ?? null;

        if (!$class) {
            throw new InvalidArgumentException("Unsupported payout provider: {$provider}");
        }

        try {
            $instance = app($class);
            if (!$instance instanceof PartnerPayoutProviderInterface) {
                throw new InvalidArgumentException("Provider {$provider} must implement PartnerPayoutProviderInterface");
            }
            return $instance;
        } catch (BindingResolutionException $e) {
            throw new InvalidArgumentException("Cannot resolve payout provider: {$provider}", 0, $e);
        }
    }

    public function supportedProviders(): array
    {
        return array_keys($this->providers);
    }
}
