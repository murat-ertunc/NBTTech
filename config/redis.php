<?php
return [
    'host' => env('REDIS_HOST', 'redis'),
    'port' => (int) env('REDIS_PORT', 6379),
];
