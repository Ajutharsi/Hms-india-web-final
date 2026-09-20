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

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
