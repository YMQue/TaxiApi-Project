<?php

use App\Http\Controllers\Api\Admin\Dashboard;
use App\Http\Controllers\Api\Admin\RideController;
use App\Http\Controllers\Api\Admin\WalletController;
use App\Http\Controllers\Api\AdminAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {

    // Public
    Route::post('login', [AdminAuthController::class, 'login']);

    // Protected
    Route::middleware(['auth:admin'])->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout']);
            //---------Wallet
        Route::get('wallet/topup-requests', [WalletController::class, 'topup_index']);
        Route::post('wallet/topup-requests/{id}/approve', [WalletController::class, 'topup_approve'])->whereNumber('id');
        Route::post('wallet/topup-requests/{id}/reject', [WalletController::class, 'topup_reject'])->whereNumber('id');

        Route::get('wallet/withdrawl-requests', [WalletController::class, 'Withdrawl_index']);
        Route::post('wallet/withdrawl-requests/{id}/approve', [WalletController::class, 'Withdrawl_approve'])->whereNumber('id');
        Route::post('wallet/withdrawl-requests/{id}/reject', [WalletController::class, 'Withdrawl_reject'])->whereNumber('id');

        Route::post('wallet/manual-adjust', [WalletController::class, 'manualAdjust']);

                //--------Rider
        Route::get('riders',[Dashboard::class,'rider_index']);
        Route::get('riders/{id}',[Dashboard::class,'rider_show'])->whereNumber('id');
        Route::post('riders/{id}/suspend', [Dashboard::class, 'suspend']);
        Route::post('riders/{id}/activate', [Dashboard::class, 'activate']);
                //---------Driver
        Route::get('drivers',[Dashboard::class,'driver_index']);
        Route::get('drivers/{id}',[Dashboard::class,'driver_show'])->whereNumber('id');
                //---------Rides
        Route::get('rides', [RideController::class, 'index']);
        Route::get('rides/{id}', [RideController::class, 'show'])->whereNumber('id');
        Route::get('rides/live', [RideController::class, 'live']);







    });
});