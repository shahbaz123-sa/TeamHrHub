<?php

namespace Modules\Chat\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ChatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Modules\Chat\Contracts\ChatRepositoryInterface::class,
            \Modules\Chat\Repositories\ChatRepository::class
        );

        $this->app->bind(
            \Modules\Chat\Contracts\Chat\MessageRepositoryInterface::class,
            \Modules\Chat\Repositories\Chat\MessageRepository::class
        );
    }

    public function boot(): void
    {
        $this->registerApiRoutes();
    }

    protected function modulePath(string $path = ''): string
    {
        return dirname(__DIR__) . "/{$path}";
    }

    protected function registerApiRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group($this->modulePath('Routes/api.php'));
    }
}
