<?php

declare(strict_types=1);

namespace Lesekloden\Screenshots;

use Illuminate\Support\ServiceProvider;

class ScreenshotsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Screenshots::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }
    }
}
