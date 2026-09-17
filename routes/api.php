<?php

use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Driver\DriverController;
use App\Http\Controllers\Api\Rider\RideController;
use App\Http\Controllers\Api\Rider\RiderWalletController;
//use App\Http\Controllers\FireBaseController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/send-otp', [AuthController::class, 'sendOtp'])->middleware('throttle:otp_send');
Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:otp_verify');
Route::post('/admin/login', [AdminAuthController::class, 'login']);
//Route::get('/firebase-test', [FireBaseController::class, 'test']);


Route::middleware('auth:sanctum')->group(function(){
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/fcm-token', [AuthController::class, 'updateFcmToken']);

     Route::middleware('role:rider')->prefix('rider')->group(function () {
            Route::get('wallet', [RiderWalletController::class, 'show']);
            Route::get('wallet/transactions', [RiderWalletController::class, 'transactions']);
            Route::post('wallet/topup', [RiderWalletController::class, 'topup']);
            

     Route::post('rides/estimate', [RideController::class, 'estimate']);
     Route::post('rides/request', [RideController::class, 'request']);

    });

    Route::middleware('role:driver')->prefix('driver')->group(function () {
        Route::get('status',[DriverController::class, 'status']);
        Route::post('rides/{id}/accept',[DriverController::class,'accept']);
        Route::post('rides/{id}/reject',[DriverController::class,'reject']);

    });

    Route::post('/admin/logout', [AdminAuthController::class, 'logout']);
});
