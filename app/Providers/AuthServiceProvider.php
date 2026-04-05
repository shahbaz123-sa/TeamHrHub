<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Auth\Repositories\AuthRepository;
use Modules\Auth\Repositories\RoleRepository;
use Modules\Auth\Repositories\UserRepository;
use Modules\Auth\Contracts\AuthRepositoryInterface;
use Modules\Auth\Contracts\RoleRepositoryInterface;
use Modules\Auth\Contracts\UserRepositoryInterface;
use Modules\Auth\Contracts\PermissionRepositoryInterface;
use Modules\Auth\Repositories\PermissionRepository;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            AuthRepositoryInterface::class,
            AuthRepository::class
        );

        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );
        $this->app->bind(
            RoleRepositoryInterface::class,
            RoleRepository::class
        );

        $this->app->bind(
            PermissionRepositoryInterface::class,
            PermissionRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
