<?php

declare(strict_types=1);

namespace Lesekloden\Screenshots;

use Illuminate\Support\ServiceProvider;
use Lesekloden\Screenshots\Console\Commands\ScreenshotsCaptureCommand;

class ScreenshotsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ScreenshotsCapture::class);
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

        $this->commands([ScreenshotsCaptureCommand::class]);
    }
}
