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

## Updating an existing installation

If you previously installed directly from GitHub, remove that repository entry and switch to the stable release:

```bash
composer config --unset repositories.lesekloden-screenshots
composer require lesekloden/lesekloden-screenshots:^1.0 --with-all-dependencies
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
