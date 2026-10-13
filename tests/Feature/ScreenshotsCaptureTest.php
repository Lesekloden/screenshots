<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Lesekloden\Screenshots\Facades\Screenshots;
use Lesekloden\Screenshots\ScreenshotsCapture;
use Spatie\Browsershot\Browsershot;

beforeEach(function () {
    $this->app->setBasePath(sys_get_temp_dir().'/lesekloden-screenshots-'.bin2hex(random_bytes(8)));
    config(['app.url' => 'https://example.test/']);
    URL::forceRootUrl('https://example.test');
    URL::forceScheme('https');
    foreach (['home' => '/', 'sortable.files' => '/sortable-files', 'sortable.table' => '/sortable-table', 'qr' => '/qr'] as $name => $path) {
        Route::get($path, fn () => 'test')->name($name);
    }
    Route::getRoutes()->refreshNameLookups();
});

afterEach(function () {
    app(Filesystem::class)->deleteDirectory(base_path());
});

it('captures the core page sections with their selectors, viewport sizes and output paths', function () {
    $captured = [];
    $this->app->bind(Browsershot::class, function () use (&$captured) {
        return new class($captured) extends Browsershot
        {
            public function __construct(public array &$captured)
            {
                parent::__construct();
            }

            public function save(string $targetPath): void
            {
                $this->captured[] = [$targetPath, $this->createScreenshotCommand($targetPath)];
                file_put_contents($targetPath, 'browser-output');
            }
        };
    });

    $pages = Screenshots::captureAll();

    expect(array_keys($pages))->toBe(['home', 'sortable-files', 'sortable-table', 'qr'])
        ->and($pages['home'])->toHaveCount(3)
        ->and($pages['sortable-files'])->toHaveCount(3)
        ->and($pages['sortable-table'])->toHaveCount(3)
        ->and($pages['qr'])->toHaveCount(2)
        ->and($captured)->toHaveCount(11)
        ->and($pages['home'][0])->toBe([
            'name' => 'with-navbar',
            'path' => resource_path('screenshots/home/screenshot-homepage-with-navbar.png'),
            'width' => 1280,
            'height' => 1024,
        ]);

    expect($captured[0][1]['url'])->toBe(route('home'))
        ->and($captured[0][1]['options']['selector'])->toBe('[data-screenshot="with-navbar"]')
        ->and($captured[0][1]['options']['viewport'])->toMatchArray(['width' => 1280, 'height' => 1024])
        ->and($captured[0][1]['options']['delay'])->toBe(250);

    foreach ($captured as [$path, $command]) {
        expect(file_get_contents($path))->toBe('browser-output');
    }

    expect($captured[3][1]['url'])->toBe(route('sortable.files'))
        ->and($captured[10][0])->toBe(resource_path('screenshots/qr/screenshot-qr-hero.png'));
});

it('captures custom URLs and normalizes filenames while allowing browser customization', function () {
    $browser = Mockery::mock(Browsershot::class);
    $this->app->instance(Browsershot::class, $browser);
    $browser->shouldReceive('setUrl')->with('https://other.test/books')->once()->andReturnSelf();
    $browser->shouldReceive('windowSize')->with(640, 480)->once()->andReturnSelf();
    $browser->shouldReceive('timeout')->with(60)->once()->andReturnSelf();
    $browser->shouldReceive('setDelay')->with(250)->once()->andReturnSelf();
    $browser->shouldReceive('setNodeBinary')->with('node')->once()->andReturnSelf();
    $browser->shouldReceive('setNpmBinary')->with('npm')->once()->andReturnSelf();
    $browser->shouldReceive('setNodeModulePath')->with(base_path('node_modules'))->once()->andReturnSelf();
    $browser->shouldReceive('waitUntilNetworkIdle')->once()->andReturnSelf();
    $browser->shouldReceive('select')->with('[data-screenshot="hero"]')->once()->andReturnSelf();
    $browser->shouldReceive('save')->with(resource_path('screenshots/books/new-releases.png'))->once();

    $result = app(ScreenshotsCapture::class)->captureScreenshot(
        'https://other.test/books', 'books', 'hero', 'New Releases!', 640, 480,
        fn (Browsershot $browser) => $browser->waitUntilNetworkIdle(),
    );

    expect($result['path'])->toBe(resource_path('screenshots/books/new-releases.png'));
});

it('rejects invalid output directories', function (string $directory) {
    app(ScreenshotsCapture::class)->captureScreenshot('/', $directory, 'hero', 'hero', 640, 480);
})->with(['', '../outside', 'books/../outside', 'books\\..\\outside', './books'])
    ->throws(InvalidArgumentException::class);

it('rejects unusable filenames', function () {
    app(ScreenshotsCapture::class)->captureScreenshot('/', 'books', 'hero', '!!!', 640, 480);
})->throws(InvalidArgumentException::class, 'cannot be converted into a filename');

it('reports a missing application URL', function () {
    config(['app.url' => '']);
    expect(fn () => app(ScreenshotsCapture::class)->captureScreenshot('/', 'home', 'hero', 'hero', 640, 480))
        ->toThrow(RuntimeException::class, 'Set a valid APP_URL');
});
