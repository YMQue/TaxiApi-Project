<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('send-otp', 'sendOtp')->middleware('throttle:otp_send');
    Route::post('verify-otp', 'verifyOtp')->middleware('throttle:otp_verify');
});

/*
|--------------------------------------------------------------------------
| Shared authenticated routes (any role)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')
    ->prefix('auth')
    ->controller(AuthController::class)
    ->group(function () {
        Route::post('register', 'register');
        Route::post('logout', 'logout');
        Route::get('me', 'me');
        Route::put('profile', 'updateProfile');
        Route::post('fcm-token', 'updateFcmToken');
    });

/*
|--------------------------------------------------------------------------
| Role based route files
|--------------------------------------------------------------------------
*/
require __DIR__ . '/api/rider.php';
require __DIR__ . '/api/driver.php';
require __DIR__ . '/api/admin.php';