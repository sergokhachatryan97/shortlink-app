<?php

use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\ExternalPanelAuth;
use App\Http\Middleware\ExternalPanelRateLimit;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminAuth::class,
            'admin.super' => EnsureSuperAdmin::class,
            'locale' => SetLocale::class,
            'auth.external_panel' => ExternalPanelAuth::class,
            'rate.external_panel' => ExternalPanelRateLimit::class,
        ]);
        $middleware->appendToGroup('web', SetLocale::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
