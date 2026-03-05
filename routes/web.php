<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ShortlinkController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ShortlinkController::class, 'index'])->name('shortlink.index');
Route::post('/generate', [ShortlinkController::class, 'generate'])->name('shortlink.generate');
Route::get('/download', [ShortlinkController::class, 'download'])->name('shortlink.download');
Route::get('/payment', [ShortlinkController::class, 'payment'])->name('shortlink.payment');
Route::post('/payment/initiate', [ShortlinkController::class, 'initiatePayment'])->name('shortlink.payment.initiate');
Route::get('/payment/success', [ShortlinkController::class, 'paymentSuccess'])->name('shortlink.payment-success');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminController::class, 'loginForm'])->name('login');
    Route::post('login', [AdminController::class, 'login']);
    Route::post('logout', [AdminController::class, 'logout'])->name('logout');
    Route::middleware('admin')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::post('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
    });
});
