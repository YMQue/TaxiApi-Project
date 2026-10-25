<?php

use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\CartypeController;
use App\Http\Controllers\Api\Admin\Dashboard;
use App\Http\Controllers\Api\Admin\PromoController as AdminPromoController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Admin\RideController;
use App\Http\Controllers\Api\Admin\SettingController;
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
        Route::middleware('admin.permission:manage_topups')->group(function () {
            Route::get('wallet/topup-requests', [WalletController::class, 'topupIndex']);
            Route::post('wallet/topup-requests/{id}/approve', [WalletController::class, 'topupApprove'])->whereNumber('id');
            Route::post('wallet/topup-requests/{id}/reject', [WalletController::class, 'topupReject'])->whereNumber('id');
        });

        Route::middleware('admin.permission:manage_withdrawals')->group(function () {
            Route::get('wallet/withdrawl-requests', [WalletController::class, 'withdrawIndex']);
            Route::post('wallet/withdrawl-requests/{id}/approve', [WalletController::class, 'withdrawApprove'])->whereNumber('id');
            Route::post('wallet/withdrawl-requests/{id}/reject', [WalletController::class, 'withdrawReject'])->whereNumber('id');
        });

        Route::post('wallet/manual-adjust', [WalletController::class, 'manualAdjust'])
            ->middleware('admin.permission:manual_adjust_wallet');

        //--------Rider
        Route::middleware('admin.permission:view_riders,manage_riders')->group(function () {
            Route::get('riders', [Dashboard::class, 'rider_index']);
            Route::get('riders/{id}', [Dashboard::class, 'rider_show'])->whereNumber('id');
        });

        Route::middleware('admin.permission:manage_riders')->group(function () {
            Route::post('riders/{id}/suspend', [Dashboard::class, 'suspend'])->whereNumber('id');
            Route::post('riders/{id}/activate', [Dashboard::class, 'activate'])->whereNumber('id');
        });

        //---------Driver
        Route::middleware('admin.permission:view_drivers,manage_drivers')->group(function () {
            Route::get('drivers', [Dashboard::class, 'driver_index']);
            Route::get('drivers/{id}', [Dashboard::class, 'driver_show'])->whereNumber('id');
        });

        //---------Rides
        Route::middleware('admin.permission:view_rides')->group(function () {
            Route::get('rides', [RideController::class, 'index']);
            Route::get('rides/live', [RideController::class, 'live']);
            Route::get('rides/{id}', [RideController::class, 'show'])->whereNumber('id');
        });

        //---------Pricing / Settings
        Route::middleware('admin.permission:manage_settings')->group(function () {
            Route::get('settings', [SettingController::class, 'index']);
            Route::put('settings', [SettingController::class, 'update']);
        });

        //---------Promos
Route::middleware('admin.permission:view_promos,manage_promos')->group(function () {
    Route::get('promos', [AdminPromoController::class, 'index']);
    Route::get('promos/{promo}/usages', [AdminPromoController::class, 'usages'])->whereNumber('promo');
});

Route::middleware('admin.permission:manage_promos')->group(function () {
    Route::post('promos', [AdminPromoController::class, 'store']);
    Route::put('promos/{promo}', [AdminPromoController::class, 'update'])->whereNumber('promo');
    Route::delete('promos/{promo}', [AdminPromoController::class, 'destroy'])->whereNumber('promo');
});
    });


Route::middleware('admin.role:super')->group(function () {
    Route::get('admins', [AdminController::class, 'index']);
    Route::post('admins', [AdminController::class, 'store']);
    Route::put('admins/{admin}', [AdminController::class, 'update'])->whereNumber('admin');
    Route::delete('admins/{admin}', [AdminController::class, 'destroy'])->whereNumber('admin');
    

    //------------Car Types Routes

    Route::get('car-types', [CartypeController::class, 'index']);
    Route::post('car-types', [CartypeController::class, 'store']);
    Route::put('car-types/{carType}', [CartypeController::class, 'update'])->whereNumber('carType');
    Route::delete('car-types/{carType}', [CartypeController::class, 'destroy'])->whereNumber('carType');
});

//---------------Reports
Route::prefix('reports')->group(function () {
    Route::get('revenue', [ReportController::class, 'revenue']);
    Route::get('rides',   [ReportController::class, 'rides']);
    Route::get('drivers', [ReportController::class, 'drivers']);
    Route::get('riders',  [ReportController::class, 'riders']);
    Route::get('drivers/{driver}/transactions', [ReportController::class, 'driverTransactions']);
    Route::get('riders/{rider}/transactions',   [ReportController::class, 'riderTransactions']);
});

Route::get('permissions', fn () => response()->json([
        'success' => true,
        'data'    => config('permissions'),
    ]));
});