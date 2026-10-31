<?php

declare(strict_types=1);

return [
    /*
    | URLs, application paths, or named routes prefixed with "route:".
    | Strings capture the full page. Arrays may specify a route (with optional
    | parameters) or path, plus name, filename, directory, width and height.
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

    // Default browser viewport and timing; entries can override dimensions.
    'width' => 1280,
    'height' => 1024,
    'timeout' => 60,
    'delay' => 250,
];
