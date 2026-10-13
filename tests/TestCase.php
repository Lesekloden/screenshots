<?php

declare(strict_types=1);

namespace Lesekloden\Screenshots\Tests;

use Lesekloden\Screenshots\ScreenshotsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ScreenshotsServiceProvider::class,
        ];
    }
}
