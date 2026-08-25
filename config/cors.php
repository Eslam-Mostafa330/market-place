<?php

/**
 * Get the Cross-Origin Resource Sharing (CORS) configuration.
 *
 * Defines the allowed paths, HTTP methods, origins, headers, and credential
 * handling rules for cross-origin requests.
 *
 * @return array<string, mixed>
 */
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter([
        env('FRONTEND_URL'),
    ]),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => (int) env('CORS_MAX_AGE', 7200),

    'supports_credentials' => true,
];
