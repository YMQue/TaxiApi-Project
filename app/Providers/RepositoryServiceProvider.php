<?php

namespace App\Providers;


use App\Contracts\Repositories\AdminRepositoryInterface;
use App\Repositories\Contracts\DriverRepositoryInterface;
use App\Repositories\Contracts\OtpRepositoryInterface;
use App\Repositories\Contracts\RideRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\WalletRepositoryInterface;
use App\Repositories\Eloquent\AdminRepository;
use App\Repositories\Eloquent\DriverRepository;
use App\Repositories\Eloquent\OtpRepository;
use App\Repositories\Eloquent\RideRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Eloquent\WalletRepository;
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
       $this->app->bind(WalletRepositoryInterface::class,WalletRepository::class);
       $this->app->bind(RideRepositoryInterface::class,RideRepository::class);

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        
    }
}
