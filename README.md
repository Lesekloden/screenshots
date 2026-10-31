# Readme

This is a thin wrapper around [Browsershot](https://github.com/spatie/browsershot) for creating custom screenshots for the Norwegian learning portal Lesekloden.no.

## Setup

Install the package in a Laravel 12 or 13 project using PHP 8.3 or later. Install Puppeteer and its browser, with Node.js and npm available on your PATH. To install directly from GitHub, run:

```bash
composer config repositories.lesekloden-screenshots vcs https://github.com/Lesekloden/screenshots.git
composer require lesekloden/lesekloden-screenshots:dev-main
npm install puppeteer
php artisan vendor:publish --tag="lesekloden-screenshots-config"
```

In `config/screenshots.php`, set `paths` to the URLs, application paths, or named routes to capture:

```php
'paths' => ['/about', 'route:home', ['route' => 'home', 'name' => 'hero']],
'output_directory' => 'storage/app/private/screenshots',
```

String entries capture full pages. Entries with a `name` capture the element with the matching attribute, such as `data-screenshot="hero"`.

Set `APP_URL` to your running application, then generate the PNG files:

```bash
php artisan generate:screenshots
```

Output is saved in page subdirectories under `storage/app/private/screenshots`.
