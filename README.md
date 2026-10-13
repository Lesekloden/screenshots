<div align="center">
    <h1>Lesekloden Screenshots</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/lesekloden/lesekloden-screenshots"><img src="https://img.shields.io/packagist/v/lesekloden/lesekloden-screenshots.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/lesekloden/lesekloden-screenshots"><img src="https://img.shields.io/packagist/php-v/lesekloden/lesekloden-screenshots.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/lesekloden/lesekloden-screenshots"><img src="https://badge.laravel.cloud/badge/lesekloden/lesekloden-screenshots?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/lesekloden/lesekloden-screenshots/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/lesekloden/lesekloden-screenshots/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/lesekloden/lesekloden-screenshots"><img src="https://img.shields.io/packagist/dt/lesekloden/lesekloden-screenshots.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Capture page sections as PNG screenshots using Laravel and Browsershot.

## Installation

You can install the package via Composer:

```bash
composer require lesekloden/lesekloden-screenshots
```

## Usage

Install Puppeteer in the host application, including its browser download:

```bash
npm install puppeteer
```

Node.js and npm must be on the PATH. Browsershot uses the host application's `node_modules` directory. Set `APP_URL` to your running application so the browser can reach its pages.

Run the capture command:

```bash
php artisan make:screenshots
```

The capture list comes from the original `core` service. The host application must define these named routes and page elements:

| Route | Screenshot elements (`data-screenshot`) | Output directory |
| --- | --- | --- |
| `home` | `with-navbar`, `hero`, `content` | `home` |
| `sortable.files` | `with-navbar`, `hero`, `content` | `sortable-files` |
| `sortable.table` | `with-navbar`, `hero`, `content` | `sortable-table` |
| `qr` | `with-navbar`, `hero` | `qr` |

Add attributes to the sections to capture, for example:

```html
<section data-screenshot="hero">...</section>
```

The command captures 11 sections, writing PNG files under `resources/screenshots` in the host application. Existing files with the same names are overwritten. All viewports are 1280 pixels wide; heights follow the original service (768 pixels for home content, 1024 for other sections). The browser uses a 60-second timeout and a 250-millisecond delay. A missing route, element, or browser failure stops the command and returns a nonzero exit code.

Generate programmatically through the facade:

```php
use Lesekloden\Screenshots\Facades\Screenshots;

$pages = Screenshots::captureAll();
```

The result groups screenshot metadata (`name`, `path`, `width`, `height`) by output directory. For individual captures, resolve the service:

```php
use Lesekloden\Screenshots\ScreenshotsCapture;

$result = app(ScreenshotsCapture::class)->captureScreenshot(
    path: 'https://example.com/books',
    directory: 'books',
    name: 'hero',
    filename: 'screenshot-books-hero',
    width: 1280,
    height: 1024,
);
```

An optional `configure` closure receives the Browsershot instance before selecting the element, allowing custom browser settings. The package supplies the capture service and command; host routes and page templates stay in the application.

## Development

```bash
composer install
composer test
composer serve
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Lesekloden Screenshots! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Simen F. Kristoffersen](https://github.com/lesekloden)
- [All Contributors](../../contributors)

## License

Lesekloden Screenshots is open-sourced software licensed under the [MIT license](LICENSE.md).
