<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NotificationController;
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
        Route::post('fcm-token', [NotificationController::class, 'storeFcmToken']);

    });
    

    Route::middleware('auth:sanctum')
    ->controller(NotificationController::class)
    ->prefix('notifications')
    ->group(function () {
        Route::get('/', 'index');
        Route::post('read-all', 'markAllAsRead');
        Route::post('{id}/read', 'markAsRead')->whereNumber('id');
    });

/*
|--------------------------------------------------------------------------
| Role based route files
|--------------------------------------------------------------------------
*/
require __DIR__ . '/api/rider.php';
require __DIR__ . '/api/driver.php';
require __DIR__ . '/api/admin.php';