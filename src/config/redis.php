<?php

/**
 * Redis Konfigurasyonu
 * 
 * Cache, session ve permission yonetimi icin Redis ayarlari
 */
return [
    'host'     => env('REDIS_HOST', 'redis'),
    'port'     => (int) env('REDIS_PORT', 6379),
    'password' => env('REDIS_PASSWORD', null),
    'database' => (int) env('REDIS_DATABASE', 0),
    
    // Cache TTL degerleri (saniye)
    'ttl' => [
        'permissions' => 3600,     // 1 saat - kullanici permission cache
        'roles'       => 3600,     // 1 saat - rol listesi cache
        'session'     => 86400,    // 24 saat - session cache
        'general'     => 1800,     // 30 dakika - genel cache
    ],
];
