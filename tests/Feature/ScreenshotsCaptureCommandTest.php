<?php

declare(strict_types=1);

use Lesekloden\Screenshots\ScreenshotsCapture;

it('reports captured screenshots', function () {
    $this->mock(ScreenshotsCapture::class)->shouldReceive('captureAll')->once()->andReturn([
        'home' => [['name' => 'hero', 'viewport' => 'desktop', 'path' => '/tmp/home.png', 'width' => 1280, 'height' => 1024]],
    ]);

    $this->artisan('generate:screenshots')
        ->expectsOutputToContain('Captured hero [desktop] (1280x1024): /tmp/home.png')
        ->expectsOutputToContain('Captured 1 screenshot across 1 page.')
        ->assertSuccessful();
});

it('reports browser failures', function () {
    $this->mock(ScreenshotsCapture::class)->shouldReceive('captureAll')->once()
        ->andThrow(new RuntimeException('The browser could not be started.'));

    $this->artisan('generate:screenshots')
        ->expectsOutputToContain('The browser could not be started.')
        ->assertFailed();
});

it('reports an empty capture list', function () {
    $this->mock(ScreenshotsCapture::class)->shouldReceive('captureAll')->once()->andReturn([]);
    $this->artisan('generate:screenshots')
        ->expectsOutputToContain('Captured 0 screenshots across 0 pages.')
        ->assertSuccessful();
});

it('counts viewport captures across pages', function () {
    $results = [];
    foreach (['desktop', 'tablet-portrait', 'tablet-landscape', 'phone'] as $viewport) {
        $results[] = ['name' => '', 'viewport' => $viewport, 'path' => '/tmp/home-'.$viewport.'.png', 'width' => 1280, 'height' => 1024];
    }
    $this->mock(ScreenshotsCapture::class)->shouldReceive('captureAll')->once()->andReturn(['home' => $results]);
    $this->artisan('generate:screenshots')
        ->expectsOutputToContain('Captured 4 screenshots across 1 page.')
        ->assertSuccessful();
});
