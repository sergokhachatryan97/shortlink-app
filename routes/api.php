<?php

use App\Http\Controllers\CoinRushWebhookController;
use App\Http\Controllers\HeleketWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/payments/heleket', [HeleketWebhookController::class, 'handle']);
Route::post('/webhooks/payments/coinrush', [CoinRushWebhookController::class, 'handle']);
