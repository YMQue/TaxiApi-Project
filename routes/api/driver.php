<?php

use App\Http\Controllers\Api\Driver\DriverController;
use App\Http\Controllers\Api\Driver\DriverWalletController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:driver'])->prefix('driver')->group(function () {

    Route::controller(DriverController::class)->group(function () {
        Route::get('status', 'status');

        Route::post('location', 'location')->middleware('throttle:driver-location');

        Route::prefix('rides/{id}')->whereNumber('id')->group(function () {
            Route::post('accept', 'accept');
            Route::post('reject', 'reject');
            Route::post('arrived', 'arrived');
            Route::post('start', 'start');
            Route::post('completed', 'complete');
        });
    });

    Route::controller(DriverWalletController::class)->prefix('wallet')->group(function () {
        Route::get('/', 'wallet');
        Route::get('transactions', 'transactions');
        Route::post('withdraw', 'withdraw');
        Route::get('withdraw-status', 'withdraw_status');
    });
});