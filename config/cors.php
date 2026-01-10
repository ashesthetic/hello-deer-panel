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

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Development origins
        'http://localhost:3000',
        'http://localhost:3100',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3100',
        'http://localhost:8000',
        'http://127.0.0.1:8000',
        'https://hellodeer.test',
        
        // Production origins
        'https://hellodeer.ca',
        'https://www.hellodeer.ca',
        'https://app.hellodeer.ca',
        'https://dashboard.hellodeer.ca',
        'https://admin.hellodeer.ca',
        
        // API domain itself (for internal requests)
        'https://api.hellodeer.ca',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['*'],

    'max_age' => 86400, // 24 hours

    'supports_credentials' => true,

]; 