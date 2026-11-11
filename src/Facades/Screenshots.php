<?php

declare(strict_types=1);

namespace Lesekloden\Screenshots\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array<string, list<array{name: string, viewport: string, path: string, width: int, height: int}>> captureAll()
 *
 * @see \Lesekloden\Screenshots\Screenshots
 */
class Screenshots extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Lesekloden\Screenshots\Screenshots::class;
    }
}
