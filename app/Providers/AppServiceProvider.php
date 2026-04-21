<?php

namespace App\Providers;

use App\Models\ShortlinkLink;
use App\Observers\ShortlinkLinkObserver;
use App\Services\BalanceService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BalanceService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ShortlinkLink::observe(ShortlinkLinkObserver::class);
    }
}
