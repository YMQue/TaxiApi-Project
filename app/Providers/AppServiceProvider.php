<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
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
    }
}
