<?php

use App\Models\Agent;
use App\Models\Company;
use App\Models\User;

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
        'api-key' => [
            'driver' => 'api-key',
            'provider' => 'companies',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => User::class,
        ],
        'agents' => [
            'driver' => 'eloquent',
            'model' => Agent::class,
        ],
        'companies' => [
            'driver' => 'api-key',
            'model' => Company::class,
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
