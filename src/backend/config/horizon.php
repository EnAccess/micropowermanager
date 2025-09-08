<?php

$queues = [
    'payment',
    'sms_gateway',
    'sms',
    'token',
    'transaction_appliance',
    'transaction_energy',
    'prospect_extract',
    'prospect_push',
];

return [
    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | This is the name of the Redis connection where Horizon will store the
    | meta information required for it to function. It includes the list
    | of supervisors, failed jobs, job metrics, and other information.
    |
    */

    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used when storing all Horizon data in Redis. You
    | may modify the prefix when you are running multiple installations
    | of Horizon on the same server so that they don't have problems.
    |
    */

    'prefix' => env('HORIZON_PREFIX', 'horizon:'),

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    |
    | This option allows you to configure when the LongWaitDetected event
    | will be fired. Every connection / queue combination may have its
    | own, unique threshold (in seconds) before this event is fired.
    |
    */

    'waits' => [
        'redis:default' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    |
    | Here you can configure for how long (in minutes) you desire Horizon to
    | persist the recent and failed jobs. Typically, recent jobs are kept
    | for one hour while all failed jobs are stored for an entire week.
    |
    */

    'trim' => [
        'recent' => 1440,
        'pending' => 10080,
        'completed' => 10080,
        'recent_failed' => 1440,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may define the queue worker settings used by your application
    | in all environments. These supervisors and settings handle all your
    | queued jobs and will be provisioned by Horizon during deployment.
    |
    */

    'environments' => [
        'production' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => $queues,
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 10,
                'tries' => 3,
            ],
        ],

        'demo' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => $queues,
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 10,
                'tries' => 3,
            ],
        ],

        'development' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => $queues,
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 10,
                'tries' => 3,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Horizon Basic Auth
    |--------------------------------------------------------------------------
    |
    | When enabled, the Horizon dashboard will be protected using simple
    | HTTP Basic Authentication. This is automatically disabled in the
    | "development" environment.
    |
    | Note: Basic Auth is a lightweight protection method — it does not
    | include features like hashing, rate limiting, or detailed logging.
    | It’s sufficient for internal tools like MicroPowerManager Horizon.
    |
    */
    'http_basic_auth' => [
        'username' => env('HORIZON_BASIC_AUTH_USERNAME'),
        'password' => env('HORIZON_BASIC_AUTH_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Horizon Notifications
    |--------------------------------------------------------------------------
    |
    | Configure to enable Horizon notifications to be sent to Slack. For example
    | when a queue has a Long Wait Detected event.
    |
    */
    'notifications' => [
        'slack_webhook_url' => env('HORIZON_SLACK_WEBHOOK_URL'),
    ],
];
