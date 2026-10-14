<?php

namespace App\Providers;

use App\Repositories\Contracts\Admin\AdminRideRepositoryinterface;
use App\Repositories\Contracts\Admin\Dashboard\RiderRepositoryInterface;
use App\Repositories\Contracts\Admin\SettingRepositoryInterface;
use App\Repositories\Contracts\Admin\WalletRepositoryInterface;
use App\Repositories\Contracts\AdminRepositoryInterface;
use App\Repositories\Contracts\DriverRepositoryInterface;
use App\Repositories\Contracts\OtpRepositoryInterface;
use App\Repositories\Contracts\RideRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\Admin\AdminRideRepository;
use App\Repositories\Eloquent\Admin\Dashboard\RiderRepository;
use App\Repositories\Eloquent\Admin\SettingRepository;
use App\Repositories\Eloquent\Admin\WalletRepository;
use App\Repositories\Eloquent\AdminRepository;
use App\Repositories\Eloquent\DriverRepository;
use App\Repositories\Eloquent\OtpRepository;
use App\Repositories\Eloquent\RideRepository;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(OtpRepositoryInterface::class, OtpRepository::class);
        $this->app->bind(DriverRepositoryInterface::class, DriverRepository::class);
        $this->app->bind(AdminRepositoryInterface::class, AdminRepository::class);
        $this->app->bind(RideRepositoryInterface::class,RideRepository::class);
        $this->app->bind(WalletRepositoryInterface::class,WalletRepository::class);
        $this->app->bind(RiderRepositoryInterface::class, RiderRepository::class);
        $this->app->bind(AdminRideRepositoryinterface::class, AdminRideRepository::class);
        $this->app->bind(SettingRepositoryInterface::class, SettingRepository::class);





    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        
    }
}
