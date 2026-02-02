<?php






return [
    'host'     => env('REDIS_HOST', 'redis'),
    'port'     => (int) env('REDIS_PORT', 6379),
    'password' => env('REDIS_PASSWORD', null),
    'database' => (int) env('REDIS_DATABASE', 0),
    
    
    'ttl' => [
        'permissions' => 3600,     
        'roles'       => 3600,     
        'session'     => 86400,    
        'general'     => 1800,     
    ],
];
