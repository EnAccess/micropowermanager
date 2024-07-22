<?php

return [
    'defaults' => [
        'guard' => 'api',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
        'agent_api' => [
            'driver' => 'jwt',
            'provider' => 'agents',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
        'agents' => [
            'driver' => 'eloquent',
            'model' => App\Models\Agent::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],
        'agents' => [
            'provider' => 'agents',
            'table' => 'password_resets',
            'expire' => 360,
        ],
    ],
];
