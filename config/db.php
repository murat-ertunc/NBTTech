<?php
return [
    'driver' => env('DB_CONNECTION', 'sqlsrv'),
    'host' => env('DB_HOST', 'localhost'),
    'port' => (int) env('DB_PORT', 1433),
    'database' => env('DB_DATABASE', 'master'),
    'username' => env('DB_USERNAME', 'sa'),
    'password' => env('DB_PASSWORD', ''),
    'trust_server_certificate' => filter_var(env('DB_TRUST_SERVER_CERT', true), FILTER_VALIDATE_BOOLEAN),
];
