<?php

namespace App\Providers;

use App\Services\BreadcrumbBuilder;
use Illuminate\Support\ServiceProvider;

class BreadcrumbServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // singleton so it’s only resolved once per request
        $this->app->singleton(BreadcrumbBuilder::class, function () {
            return new BreadcrumbBuilder();
        });
    }

    public function boot(): void
    {
        //
    }
}
