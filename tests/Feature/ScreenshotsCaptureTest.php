<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Lesekloden\Screenshots\Facades\Screenshots;
use Lesekloden\Screenshots\ScreenshotsCapture;
use Lesekloden\Screenshots\ScreenshotsServiceProvider;
use Spatie\Browsershot\Browsershot;

beforeEach(function () {
    $this->app->setBasePath(sys_get_temp_dir().'/lesekloden-screenshots-'.bin2hex(random_bytes(8)));
    config(['app.url' => 'https://example.test/', 'screenshots.viewports' => ['desktop' => ['width' => 1280, 'height' => 1024]]]);
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

    config(['screenshots.paths' => [
        ['route' => 'home', 'directory' => 'home', 'name' => 'with-navbar', 'filename' => 'screenshot-homepage-with-navbar'],
        ['route' => 'home', 'directory' => 'home', 'name' => 'hero', 'filename' => 'screenshot-homepage-hero'],
        ['route' => 'home', 'directory' => 'home', 'name' => 'content', 'filename' => 'screenshot-homepage-content'],
        ['route' => 'sortable.files', 'directory' => 'sortable-files', 'name' => 'with-navbar', 'filename' => 'screenshot-sortable-files-with-navbar'],
        ['route' => 'sortable.files', 'directory' => 'sortable-files', 'name' => 'hero', 'filename' => 'screenshot-sortable-files-hero'],
        ['route' => 'sortable.files', 'directory' => 'sortable-files', 'name' => 'content', 'filename' => 'screenshot-sortable-files-content'],
        ['route' => 'sortable.table', 'directory' => 'sortable-table', 'name' => 'with-navbar', 'filename' => 'screenshot-sortable-table-with-navbar'],
        ['route' => 'sortable.table', 'directory' => 'sortable-table', 'name' => 'hero', 'filename' => 'screenshot-sortable-table-hero'],
        ['route' => 'sortable.table', 'directory' => 'sortable-table', 'name' => 'content', 'filename' => 'screenshot-sortable-table-content'],
        ['route' => 'qr', 'directory' => 'qr', 'name' => 'with-navbar', 'filename' => 'screenshot-qr-with-navbar'],
        ['route' => 'qr', 'directory' => 'qr', 'name' => 'hero', 'filename' => 'screenshot-qr-hero'],
    ]]);
    $pages = Screenshots::captureAll();

    expect(array_keys($pages))->toBe(['home', 'sortable-files', 'sortable-table', 'qr'])
        ->and($pages['home'])->toHaveCount(3)
        ->and($pages['sortable-files'])->toHaveCount(3)
        ->and($pages['sortable-table'])->toHaveCount(3)
        ->and($pages['qr'])->toHaveCount(2)
        ->and($captured)->toHaveCount(11)
        ->and($pages['home'][0])->toBe([
            'name' => 'with-navbar',
            'viewport' => 'desktop',
            'path' => base_path('storage/app/private/screenshots').DIRECTORY_SEPARATOR.'home'.DIRECTORY_SEPARATOR.'screenshot-homepage-with-navbar-desktop.png',
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
        ->and($captured[10][0])->toBe(base_path('storage/app/private/screenshots').DIRECTORY_SEPARATOR.'qr'.DIRECTORY_SEPARATOR.'screenshot-qr-hero-desktop.png');
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
    $browser->shouldReceive('save')->with(base_path('storage/app/private/screenshots').DIRECTORY_SEPARATOR.'books'.DIRECTORY_SEPARATOR.'new-releases.png')->once();

    $result = app(ScreenshotsCapture::class)->captureScreenshot(
        'https://other.test/books', 'books', 'hero', 'New Releases!', 640, 480,
        fn (Browsershot $browser) => $browser->waitUntilNetworkIdle(),
    );

    expect($result['path'])->toBe(base_path('storage/app/private/screenshots').DIRECTORY_SEPARATOR.'books'.DIRECTORY_SEPARATOR.'new-releases.png');
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

it('captures configured full pages into a custom output directory', function () {
    config(['screenshots.paths' => ['/about', 'route:qr'], 'screenshots.output_directory' => 'storage/captures']);
    $browser = Mockery::mock(Browsershot::class);
    $this->app->instance(Browsershot::class, $browser);
    $browser->shouldReceive('setUrl')->with('https://example.test/about')->once()->andReturnSelf();
    $browser->shouldReceive('setUrl')->with(route('qr'))->once()->andReturnSelf();
    foreach (['windowSize', 'timeout', 'setDelay', 'setNodeBinary', 'setNpmBinary', 'setNodeModulePath', 'fullPage'] as $method) {
        $browser->shouldReceive($method)->twice()->andReturnSelf();
    }
    $browser->shouldReceive('save')->with(base_path('storage/captures').DIRECTORY_SEPARATOR.'about'.DIRECTORY_SEPARATOR.'about-desktop.png')->once();
    $browser->shouldReceive('save')->with(base_path('storage/captures').DIRECTORY_SEPARATOR.'qr'.DIRECTORY_SEPARATOR.'qr-desktop.png')->once();
    expect(Screenshots::captureAll())->toHaveKeys(['about', 'qr']);
});

it('starts with an empty configurable capture list', function () {
    expect(config('screenshots.paths'))->toBe([])
        ->and(config('screenshots.output_directory'))->toBe('storage/app/private/screenshots')
        ->and(Screenshots::captureAll())->toBe([]);
});

it('publishes screenshot configuration', function () {
    $this->artisan('vendor:publish', ['--tag' => 'lesekloden-screenshots-config', '--force' => true])->assertSuccessful();
    $paths = ServiceProvider::pathsToPublish(ScreenshotsServiceProvider::class, 'lesekloden-screenshots-config');
    expect($paths)->toHaveCount(1);
    $published = array_values($paths)[0];
    expect(is_file($published))->toBeTrue();
    unlink($published);
});

it('captures every default viewport with shared timing and distinct filenames', function () {
    config(['screenshots.viewports' => (require __DIR__.'/../../config/screenshots.php')['viewports'],
        'screenshots.paths' => [['path' => '/about', 'name' => 'hero', 'filename' => 'About Hero']],
        'screenshots.timeout' => 90, 'screenshots.delay' => 500]);
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
                $this->captured[] = $this->createScreenshotCommand($targetPath);
            }
        };
    });
    $results = Screenshots::captureAll()['about'];
    expect(array_column($results, 'viewport'))->toBe(['desktop', 'tablet-portrait', 'tablet-landscape', 'phone']);
    foreach (config('screenshots.viewports') as $viewport => $dimensions) {
        $index = array_search($viewport, array_column($results, 'viewport'), true);
        expect(basename($results[$index]['path']))->toBe('about-hero-'.$viewport.'.png')
            ->and($captured[$index]['options']['viewport'])->toMatchArray($dimensions)
            ->and($captured[$index]['options']['delay'])->toBe(500)
            ->and($captured[$index]['options']['timeout'])->toBe(90000)
            ->and($captured[$index]['options']['selector'])->toBe('[data-screenshot="hero"]');
    }
});

it('captures only selected viewports as full pages', function () {
    config(['screenshots.viewports' => (require __DIR__.'/../../config/screenshots.php')['viewports'],
        'screenshots.paths' => [['path' => '/about', 'viewports' => ['phone', 'desktop']]]]);
    config(['screenshots.viewports.phone' => ['width' => 412, 'height' => 915]]);
    $browser = Mockery::mock(Browsershot::class);
    $this->app->instance(Browsershot::class, $browser);
    $browser->shouldReceive('windowSize')->with(412, 915)->once()->andReturnSelf();
    $browser->shouldReceive('windowSize')->with(1280, 1024)->once()->andReturnSelf();
    foreach (['setUrl', 'timeout', 'setDelay', 'setNodeBinary', 'setNpmBinary', 'setNodeModulePath', 'fullPage', 'save'] as $method) {
        $browser->shouldReceive($method)->twice()->andReturnSelf();
    }
    expect(array_column(Screenshots::captureAll()['about'], 'viewport'))->toBe(['phone', 'desktop']);
});

it('rejects invalid viewport configuration before starting a browser', function ($viewports, $selection) {
    config(['screenshots.viewports' => $viewports,
        'screenshots.paths' => ['/valid', ['path' => '/about', 'viewports' => $selection]]]);
    $this->app->bind(Browsershot::class, fn () => throw new RuntimeException('Browser must not start.'));
    Screenshots::captureAll();
})->with([
    'not an array' => [false, ['desktop']],
    'empty definitions' => [[], ['desktop']],
    'unsafe name' => [['../desktop' => ['width' => 1280, 'height' => 1024]], ['desktop']],
    'missing height' => [['desktop' => ['width' => 1280]], ['desktop']],
    'zero width' => [['desktop' => ['width' => 0, 'height' => 1024]], ['desktop']],
    'fractional height' => [['desktop' => ['width' => 1280, 'height' => 1.5]], ['desktop']],
    'unknown selection' => [['desktop' => ['width' => 1280, 'height' => 1024]], ['phone']],
    'empty selection' => [['desktop' => ['width' => 1280, 'height' => 1024]], []],
    'null selection' => [['desktop' => ['width' => 1280, 'height' => 1024]], null],
    'string selection' => [['desktop' => ['width' => 1280, 'height' => 1024]], 'desktop'],
    'duplicate selection' => [['desktop' => ['width' => 1280, 'height' => 1024]], ['desktop', 'desktop']],
])->throws(InvalidArgumentException::class);
