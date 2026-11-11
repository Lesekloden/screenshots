# Lesekloden Screenshots

This is a thin wrapper around [Browsershot](https://github.com/spatie/browsershot) for creating custom screenshots for the Norwegian learning portal Lesekloden.no.

## Setup

Install the package in a Laravel 12 or 13 project using PHP 8.3 or later. Install Puppeteer and its browser, with Node.js and npm available on your PATH:

```bash
composer require lesekloden/lesekloden-screenshots
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

## Viewports

Each page or section is captured at all four configured viewports by default:

```php
'viewports' => [
    'desktop' => ['width' => 1280, 'height' => 1024],
    'tablet-portrait' => ['width' => 768, 'height' => 1024],
    'tablet-landscape' => ['width' => 1024, 'height' => 768],
    'phone' => ['width' => 390, 'height' => 844],
],

// Shared across all viewports.
'timeout' => 60, // Seconds.
'delay' => 250, // Milliseconds.
```

Customize dimensions or add named viewports to match your application's breakpoints. Names must start with a lowercase letter and contain lowercase letters, numbers, and single hyphens. Dimensions must be positive integers. These presets change browser dimensions; they do not emulate device touch input or user agents.

Select a subset for an individual entry:

```php
'paths' => [
    '/about', // All configured viewports; captures the full page.
    [
        'route' => 'home',
        'name' => 'hero', // Selects [data-screenshot="hero"].
        'filename' => 'homepage-hero', // Base filename without an extension.
        'viewports' => ['desktop', 'phone'],
    ],
],
```

Selections must be non-empty lists of unique, configured names. Entries use dimensions from the named viewports.

Filenames include the viewport name:

```text
home/homepage-hero-desktop.png
home/homepage-hero-phone.png
about/about-desktop.png
about/about-tablet-portrait.png
about/about-tablet-landscape.png
about/about-phone.png
```

Full-page images include the whole page and may exceed the viewport height. Named elements are cropped to their matching element. Batch results from `Screenshots::captureAll()` include `name`, `viewport`, `path`, `width`, and `height`; dimensions describe the browser viewport, not necessarily the final image size. The direct `ScreenshotsCapture::captureScreenshot()` API still captures a single image using explicit dimensions and a base filename.

## Screenshots for pull requests

In the consuming Laravel project's CI workflow, install the package and Puppeteer, build application assets, prepare representative data, and start the application. Set `APP_URL` to the running server and run `php artisan generate:screenshots` once the server is ready.

Upload `storage/app/private/screenshots` as a workflow artifact so reviewers can download all viewport captures. A separate workflow step can publish image links or a preview to the pull request. This package generates the PNG files; it does not upload files or post GitHub comments.

For useful before/after reviews, capture the base branch and PR branch into separate artifact directories using the same browser, data, fonts, and viewport configuration. The stable viewport filenames make corresponding images easy to compare.

## Updating an existing installation

If you previously installed directly from GitHub, remove that repository entry and switch to the stable release:

```bash
composer config --unset repositories.lesekloden-screenshots
composer require lesekloden/lesekloden-screenshots:^2.0 --with-all-dependencies
```

For subsequent compatible releases:

```bash
composer update lesekloden/lesekloden-screenshots
```

Review the [changelog](CHANGELOG.md) before updating. To add new settings to an already published config file, compare it with `vendor/lesekloden/lesekloden-screenshots/config/screenshots.php` and merge the changes into your application's config.

## Development and releases

Run `composer install` and `composer test` before pushing changes. Use Conventional Commits (or squash-merge PRs with a Conventional Commit title):

- `fix: ...` for a patch release.
- `feat: ...` for a minor release.
- `feat!: ...` or a `BREAKING CHANGE:` footer for a major release.

After the current `main` commit passes CI, release-please prepares or updates a release PR with the next version and changelog. Review and merge that PR to publish the GitHub release and version tag; the Packagist webhook then indexes it automatically. No repeated Packagist submission or manual tag is needed.

Update this README in the same PR whenever installation, requirements, configuration, or usage changes. Documentation-only changes do not normally require a release.

The workflow uses GitHub's built-in token. Its generated release PRs do not trigger additional CI runs; the merged `main` commit must pass the full test suite before a release is created. Package versions come from Git tags, so do not add a `version` field to `composer.json`.
