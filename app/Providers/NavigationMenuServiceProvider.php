<?php

namespace App\Providers;

use App\Services\NavigationMenuBuilder;
use Illuminate\Support\ServiceProvider;

class NavigationMenuServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(NavigationMenuBuilder::class, function () {
            return new NavigationMenuBuilder();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
