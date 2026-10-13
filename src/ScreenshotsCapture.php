<?php

declare(strict_types=1);

namespace Lesekloden\Screenshots;

use Closure;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Spatie\Browsershot\Browsershot;

class ScreenshotsCapture
{
    public function __construct(
        private readonly Filesystem $filesystem,
    ) {}

    /**
     * @return array<string, list<array{name: string, path: string, width: int, height: int}>>
     */
    public function captureAll(): array
    {
        return [
            'home' => $this->captureHome(),
            'sortable-files' => $this->captureSortableFiles(),
            'sortable-table' => $this->captureSortableTable(),
            'qr' => $this->captureQr(),
        ];
    }

    /**
     * @return list<array{name: string, path: string, width: int, height: int}>
     */
    private function captureHome(): array
    {
        return [
            $this->captureScreenshot(route('home'), 'home', 'with-navbar', 'screenshot-homepage-with-navbar', 1280, 1024),
            $this->captureScreenshot(route('home'), 'home', 'hero', 'screenshot-homepage-hero', 1280, 1024),
            $this->captureScreenshot(route('home'), 'home', 'content', 'screenshot-homepage-content', 1280, 768),
        ];
    }

    /**
     * @return list<array{name: string, path: string, width: int, height: int}>
     */
    private function captureSortableFiles(): array
    {
        return [
            $this->captureScreenshot(route('sortable.files'), 'sortable-files', 'with-navbar', 'screenshot-sortable-files-with-navbar', 1280, 1024),
            $this->captureScreenshot(route('sortable.files'), 'sortable-files', 'hero', 'screenshot-sortable-files-hero', 1280, 1024),
            $this->captureScreenshot(route('sortable.files'), 'sortable-files', 'content', 'screenshot-sortable-files-content', 1280, 1024),
        ];
    }

    /**
     * @return list<array{name: string, path: string, width: int, height: int}>
     */
    private function captureSortableTable(): array
    {
        return [
            $this->captureScreenshot(route('sortable.table'), 'sortable-table', 'with-navbar', 'screenshot-sortable-table-with-navbar', 1280, 1024),
            $this->captureScreenshot(route('sortable.table'), 'sortable-table', 'hero', 'screenshot-sortable-table-hero', 1280, 1024),
            $this->captureScreenshot(route('sortable.table'), 'sortable-table', 'content', 'screenshot-sortable-table-content', 1280, 1024),
        ];
    }

    /**
     * @return list<array{name: string, path: string, width: int, height: int}>
     */
    private function captureQr(): array
    {
        return [
            $this->captureScreenshot(route('qr'), 'qr', 'with-navbar', 'screenshot-qr-with-navbar', 1280, 1024),
            $this->captureScreenshot(route('qr'), 'qr', 'hero', 'screenshot-qr-hero', 1280, 1024),
        ];
    }

    /**
     * @return array{name: string, path: string, width: int, height: int}
     */
    public function captureScreenshot(
        string $path,
        string $directory,
        string $name,
        string $filename,
        int $width,
        int $height,
        ?Closure $configure = null,
    ): array {
        $directory = $this->safeDirectory($directory);
        $outputDirectory = resource_path('screenshots').DIRECTORY_SEPARATOR.$directory;
        $outputFilename = $this->filenameFor($filename);
        $targetPath = $outputDirectory.DIRECTORY_SEPARATOR.$outputFilename;

        $this->filesystem->ensureDirectoryExists($outputDirectory);

        $browser = $this->browser($this->urlForPath($path), $width, $height);

        if ($configure !== null) {
            $configure($browser);
        }

        $browser
            ->select($this->selectorFor($name))
            ->save($targetPath);

        return [
            'name' => $name,
            'path' => $targetPath,
            'width' => $width,
            'height' => $height,
        ];
    }

    private function browser(string $url, int $width, int $height): Browsershot
    {
        return app(Browsershot::class)->setUrl($url)
            ->windowSize($width, $height)
            ->timeout(60)
            ->setDelay(250)
            ->setNodeBinary('node')
            ->setNpmBinary('npm')
            ->setNodeModulePath(base_path('node_modules'));
    }

    private function urlForPath(string $path): string
    {
        if (filter_var($path, FILTER_VALIDATE_URL) !== false) {
            return $path;
        }

        $baseUrl = rtrim((string) config('app.url', 'http://127.0.0.1:8000'), '/');

        if ($baseUrl === '') {
            throw new RuntimeException('Set a valid APP_URL before capturing screenshots.');
        }

        return $baseUrl.'/'.ltrim($path, '/');
    }

    private function selectorFor(string $name): string
    {
        $value = addcslashes($name, '\\"');

        return sprintf('[data-screenshot="%s"]', $value);
    }

    private function filenameFor(string $filename): string
    {
        $normalizedFilename = Str::slug($filename);

        if ($normalizedFilename === '') {
            throw new InvalidArgumentException("The screenshot filename [{$filename}] cannot be converted into a filename.");
        }

        return $normalizedFilename.'.png';
    }

    private function safeDirectory(string $directory): string
    {
        $directory = trim($directory, " /\\\t\n\r\0\x0B");

        if ($directory === '') {
            throw new InvalidArgumentException('The screenshot output directory must not be empty.');
        }

        $segments = preg_split('#[\\\\/]#', $directory, -1, PREG_SPLIT_NO_EMPTY);

        if ($segments === false) {
            throw new InvalidArgumentException('The screenshot output directory is invalid.');
        }

        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '..') {
                throw new InvalidArgumentException('The screenshot output directory may not contain path traversal segments.');
            }
        }

        return implode(DIRECTORY_SEPARATOR, $segments);
    }
}
