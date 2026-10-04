<?php

namespace App\Providers;

use App\Models\Admin;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
         RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    RateLimiter::for('otp_send', function (Request $request) {
        $key = $request->input('phone') ?: $request->ip();
        return Limit::perMinute(3)->by($key);
    });

    RateLimiter::for('otp_verify', function (Request $request) {
        $key = $request->input('phone') ?: $request->ip();
        return Limit::perMinute(10)->by($key);
    });

    // AppServiceProvider::boot()
RateLimiter::for('driver-location', function (Request $request) {
    // حد أقصى 30 طلب في الدقيقة لكل سائق (حماية لو التطبيق بعت بسرعة زيادة)
    return Limit::perMinute(30)->by($request->user()->id);
});


Relation::enforceMorphMap([
    'admin'  => Admin::class,
    'rider' => \App\Models\User::class,
    'driver' => \App\Models\Driver::class,
    'ride'    => \App\Models\Ride::class,
    'topup'   => \App\Models\TopupRequest::class,
    'withdrawal' => \App\Models\WithdrawalRequest::class,
    'topup_request' => \App\Models\TopupRequest::class,
    
]);
    }
}
