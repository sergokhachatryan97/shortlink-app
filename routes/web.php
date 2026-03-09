<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\LinksController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShortlinkController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ShortlinkController::class, 'index'])->name('shortlink.index');
Route::post('/generate', [ShortlinkController::class, 'generate'])->name('shortlink.generate');
Route::get('/download', [ShortlinkController::class, 'download'])->name('shortlink.download');
Route::get('/payment', [ShortlinkController::class, 'payment'])->name('shortlink.payment');
Route::post('/payment/initiate', [ShortlinkController::class, 'initiatePayment'])->name('shortlink.payment.initiate');
Route::get('/payment/success', [ShortlinkController::class, 'paymentSuccess'])->name('shortlink.payment-success');
Route::post('/payment/tron/prepare', [ShortlinkController::class, 'prepareTronPayment'])->name('shortlink.payment-tron-prepare');
Route::get('/payment/tron/success', [ShortlinkController::class, 'paymentTronSuccess'])->name('shortlink.payment-tron-success');

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('auth.login');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('auth.register');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::get('/auth/telegram', [AuthController::class, 'telegram'])->name('auth.telegram');
Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/links', [LinksController::class, 'index'])->name('links.index');
    Route::get('/links/download', [LinksController::class, 'download'])->name('links.download');
    Route::get('/balance', [BalanceController::class, 'index'])->name('balance.index');
    Route::post('/balance/topup/prepare', [BalanceController::class, 'prepareTopup'])->name('balance.topup.prepare');
    Route::get('/balance/tron/success', [BalanceController::class, 'tronTopupSuccess'])->name('balance.tron.success');
    Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/subscription/purchase', [SubscriptionController::class, 'purchase'])->name('subscription.purchase');
    Route::post('/subscription/upgrade', [SubscriptionController::class, 'upgrade'])->name('subscription.upgrade');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminController::class, 'loginForm'])->name('login');
    Route::post('login', [AdminController::class, 'login']);
    Route::match(['get', 'post'], 'logout', [AdminController::class, 'logout'])->name('logout');
    Route::middleware('admin')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::post('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
        Route::post('/plans/{plan}', [AdminController::class, 'updatePlan'])->name('plans.update');
    });
});
