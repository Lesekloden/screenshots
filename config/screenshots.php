<?php

declare(strict_types=1);

return [
    /*
    | URLs, application paths, or named routes prefixed with "route:".
    | Strings capture the full page. Arrays may specify a route (with optional
    | parameters) or path, plus name, filename, directory and a list of viewport names.
    | The name selects a matching data-screenshot element when supplied.
    |
    | Examples:
    | '/about',
    | 'https://lesekloden.no/om',
    | 'route:home',
    | ['route' => 'home', 'name' => 'hero', 'filename' => 'homepage-hero'],
    */
    'paths' => [],

    // Application-relative or absolute directory for the PNG output.
    'output_directory' => 'storage/app/private/screenshots',

    // Named browser viewports; each entry captures all unless it selects a subset.
    'viewports' => [
        'desktop' => ['width' => 1280, 'height' => 1024],
        'tablet-portrait' => ['width' => 768, 'height' => 1024],
        'tablet-landscape' => ['width' => 1024, 'height' => 768],
        'phone' => ['width' => 390, 'height' => 844],
    ],

    // Shared browser timing: timeout in seconds, delay in milliseconds.
    'timeout' => 60,
    'delay' => 250,
];
