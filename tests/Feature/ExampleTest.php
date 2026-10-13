<?php

declare(strict_types=1);

use Lesekloden\Screenshots\Screenshots;

it('resolves the singleton', function () {
    expect(app(Screenshots::class))->toBeInstanceOf(Screenshots::class);
});

it('returns the same instance from the container', function () {
    expect(app(Screenshots::class))->toBe(app(Screenshots::class));
});
