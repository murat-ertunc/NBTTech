<?php
return [
    'name' => env('APP_NAME', 'NbtProject'),
    'env' => env('APP_ENV', 'local'),
    'debug' => filter_var(env('APP_DEBUG', true), FILTER_VALIDATE_BOOLEAN),
    'url' => env('APP_URL', 'http://localhost'),
    'logo' => env('APP_LOGO', '/assets/logo.png'),
];
