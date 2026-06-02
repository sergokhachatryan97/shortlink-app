<?php

use App\Http\Controllers\HeleketPayoutWebhookController;
use App\Http\Controllers\HeleketWebhookController;
use App\Http\Controllers\PanelApi\PanelApiController;
use App\Http\Controllers\YooKassaWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/payments/heleket', [HeleketWebhookController::class, 'handle']);
Route::post('/webhooks/payments/yookassa', [YooKassaWebhookController::class, 'handle']);
Route::post('/webhooks/heleket/payout', [HeleketPayoutWebhookController::class, 'handle']);
Route::get('/v2', PanelApiController::class)
    ->middleware(['auth.external_panel', 'rate.external_panel']);
