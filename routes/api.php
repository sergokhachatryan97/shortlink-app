<?php

use App\Http\Controllers\HeleketWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/payments/heleket', [HeleketWebhookController::class, 'handle']);
