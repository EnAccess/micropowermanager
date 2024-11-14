<?php

return [
    // 'uri' => env('LARAVEL_ERD_URI', 'laravel-erd'),
    // 'storage_path' => storage_path('framework/cache/laravel-erd'),
    // 'extension' => env('LARAVEL_ERD_EXTENSION', 'sql'),
    // 'middleware' => [],
    // 'binary' => [
    //     'erd-go' => env('LARAVEL_ERD_GO', '/usr/local/bin/erd-go'),
    //     'dot' => env('LARAVEL_ERD_DOT', '/usr/local/bin/dot'),
    // ],
    'connections' => [
        // as same as your current database name
        'micro_power_manager' => [
            // 'driver' => 'mysql',
            // 'host' => 'db',
            // 'port' => '3306',
            'database' => 'erd', // <-- this is a fake database, don't set this to your production database or development database
            // 'username' => 'root',
            // 'password' => 'wF9zLp2qRxaS2e',
            // 'unix_socket' => '',
            // 'charset' => 'utf8mb4',
            // 'collation' => 'utf8mb4_unicode_ci',
            // 'prefix' => '',
            // 'prefix_indexes' => true,
            // 'strict' => true,
            // 'engine' => null,
            // 'options' => [],
        ],
    ],
];
