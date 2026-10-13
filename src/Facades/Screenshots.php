<?php

declare(strict_types=1);

namespace Lesekloden\Screenshots\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Lesekloden\Screenshots\Screenshots
 */
class Screenshots extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Lesekloden\Screenshots\Screenshots::class;
    }
}
