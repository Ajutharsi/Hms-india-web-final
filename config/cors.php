<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Needed for the Flutter web build, which runs on its own dev-server
    | origin (e.g. http://localhost:5xxxx) and calls this app's /api/*
    | routes on a different origin (http://127.0.0.1:8000) — without this,
    | the browser blocks the response before Flutter ever sees it.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Browser origins allowed to call this API. The Flutter app is not a
    // browser and is unaffected by CORS, so this only needs the web front
    // ends: set CORS_ALLOWED_ORIGINS=https://hms.example.com in .env.
    'allowed_origins' => array_filter(explode(',', (string) env('CORS_ALLOWED_ORIGINS', env('APP_URL', 'http://localhost')))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
