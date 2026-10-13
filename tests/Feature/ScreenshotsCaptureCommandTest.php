<?php

declare(strict_types=1);

use Lesekloden\Screenshots\ScreenshotsCapture;

it('reports captured screenshots', function () {
    $this->mock(ScreenshotsCapture::class)->shouldReceive('captureAll')->once()->andReturn([
        'home' => [['name' => 'hero', 'path' => '/tmp/home.png', 'width' => 1280, 'height' => 1024]],
    ]);

    $this->artisan('make:screenshots')
        ->expectsOutputToContain('Captured hero (1280x1024): /tmp/home.png')
        ->expectsOutputToContain('Captured 1 screenshot across 1 page.')
        ->assertSuccessful();
});

it('reports browser failures', function () {
    $this->mock(ScreenshotsCapture::class)->shouldReceive('captureAll')->once()
        ->andThrow(new RuntimeException('The browser could not be started.'));

    $this->artisan('make:screenshots')
        ->expectsOutputToContain('The browser could not be started.')
        ->assertFailed();
});

it('reports an empty capture list', function () {
    $this->mock(ScreenshotsCapture::class)->shouldReceive('captureAll')->once()->andReturn([]);
    $this->artisan('make:screenshots')
        ->expectsOutputToContain('Captured 0 screenshots across 0 pages.')
        ->assertSuccessful();
});
