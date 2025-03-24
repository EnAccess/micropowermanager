<?php

return [
    'uri' => env('LARAVEL_ERD_URI', 'laravel-erd'),
    'storage_path' => storage_path('framework/cache/laravel-erd'),
    'extension' => env('LARAVEL_ERD_EXTENSION', 'sql'),
    'middleware' => [],
    'binary' => [
        'erd-go' => env('LARAVEL_ERD_GO', '/usr/local/bin/erd-go'),
        'dot' => env('LARAVEL_ERD_DOT', '/usr/local/bin/dot'),
    ],
    'connections' => [
        'micro_power_manager' => [
            'database' => 'erd', // <-- this is a fake database, don't set this to your production database or development database
        ],
        'tenant' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => 'erd', // <-- this is a fake database, don't set this to your production database or development database
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],
    ],
];
