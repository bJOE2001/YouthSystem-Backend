<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'login', 'logout', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(array_map('trim', explode(',', env(
        'CORS_ALLOWED_ORIGINS',
        env('FRONTEND_URLS', 'http://localhost:9000,http://127.0.0.1:9000,http://localhost:9002,http://192.168.8.58:9002')
    )))),

    'allowed_origins_patterns' => array_filter(array_map('trim', explode(',', env(
        'CORS_ALLOWED_ORIGINS_PATTERNS',
        '#^http://(localhost|127\.0\.0\.1|192\.168\.\d+\.\d+|10\.\d+\.\d+\.\d+)(:\d+)?$#'
    )))),

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Content-Disposition', 'Content-Type', 'Content-Length'],

    'max_age' => 0,

    'supports_credentials' => env('CORS_SUPPORTS_CREDENTIALS', true),

];
