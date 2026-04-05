<?php

namespace Modules\Auth\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 
    }

    public function boot(): void
    {
        $this->app->booted(function () {
            app(PermissionRegistrar::class)->getPermissions();
        });

        $this->loadMigrations();
    }

    protected function modulePath(string $path = ''): string
    {
        return dirname(__DIR__) . "/{$path}";
    }

    protected function loadMigrations(): void
    {
        $this->loadMigrationsFrom($this->modulePath('Database/Migrations'));
    }
}
