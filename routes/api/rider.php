<?php

use App\Http\Controllers\Api\Rider\RideController;
use App\Http\Controllers\Api\Rider\RiderWalletController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:rider'])->prefix('rider')->group(function () {

    Route::controller(RiderWalletController::class)->prefix('wallet')->group(function () {
        Route::get('/', 'wallet');
        Route::get('transactions', 'transactions');
        Route::post('topup', 'topup');
    });

    Route::controller(RideController::class)->prefix('rides')->group(function () {
        Route::get('/', 'index');
        Route::post('estimate', 'estimate');
        Route::post('request', 'request');
        Route::get('{id}', 'show')->whereNumber('id');
        Route::post('{id}/rate','rate')->whereNumber('id');
        Route::post('{id}/cancel','cancel')->whereNumber('id');

    });
});