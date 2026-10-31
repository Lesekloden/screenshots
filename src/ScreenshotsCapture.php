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
        $paths = config('screenshots.paths', []);

        if (! is_array($paths)) {
            throw new InvalidArgumentException('The screenshots.paths setting must be an array.');
        }

        $pages = [];

        foreach ($paths as $entry) {
            if (is_string($entry)) {
                $entry = ['path' => $entry];
            }

            if (! is_array($entry)) {
                throw new InvalidArgumentException('Each screenshot path must be a string or an array.');
            }

            $path = isset($entry['route'])
                ? route($entry['route'], $entry['parameters'] ?? [])
                : ($entry['path'] ?? '');

            if (! is_string($path) || trim($path) === '') {
                throw new InvalidArgumentException('Each screenshot entry must specify a path or route.');
            }

            $url = $this->urlForPath($path);
            $slug = Str::slug(trim((string) parse_url($url, PHP_URL_PATH), '/')) ?: 'home';
            $directory = $entry['directory'] ?? $slug;
            $name = $entry['name'] ?? '';
            $filename = $entry['filename'] ?? ($name === '' ? $slug : $slug.'-'.$name);
            $pages[$directory][] = $this->captureScreenshot(
                $url, $directory, $name, $filename,
                (int) ($entry['width'] ?? config('screenshots.width', 1280)),
                (int) ($entry['height'] ?? config('screenshots.height', 1024)),
            );
        }

        return $pages;
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
        $root = (string) config('screenshots.output_directory', 'storage/app/private/screenshots');

        if (trim($root) === '') {
            throw new InvalidArgumentException('The screenshots.output_directory setting must not be empty.');
        }

        $root = Str::startsWith($root, DIRECTORY_SEPARATOR) ? $root : base_path($root);
        $outputDirectory = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$directory;

        if ($width < 1 || $height < 1) {
            throw new InvalidArgumentException('Screenshot dimensions must be positive.');
        }
        $outputFilename = $this->filenameFor($filename);
        $targetPath = $outputDirectory.DIRECTORY_SEPARATOR.$outputFilename;

        $this->filesystem->ensureDirectoryExists($outputDirectory);

        $browser = $this->browser($this->urlForPath($path), $width, $height);

        if ($configure !== null) {
            $configure($browser);
        }

        if ($name !== '') {
            $browser->select($this->selectorFor($name));
        } else {
            $browser->fullPage();
        }

        $browser->save($targetPath);

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
            ->timeout((int) config('screenshots.timeout', 60))
            ->setDelay((int) config('screenshots.delay', 250))
            ->setNodeBinary('node')
            ->setNpmBinary('npm')
            ->setNodeModulePath(base_path('node_modules'));
    }

    private function urlForPath(string $path): string
    {
        if (str_starts_with($path, 'route:')) {
            return route(substr($path, 6));
        }

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
