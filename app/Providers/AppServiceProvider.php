<?php

namespace App\Providers;

use Illuminate\Routing\Router;
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
    public function boot(Router $router): void
    {
        $router->aliasMiddleware('admin', \App\Http\Middleware\EnsureAdmin::class);
        $router->aliasMiddleware('manager', \App\Http\Middleware\EnsureManager::class);
        $router->aliasMiddleware('staff', \App\Http\Middleware\EnsureStaff::class);
    }
}
