<?php

use Monolog\Handler\FilterHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'mpm_stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'mpm_stack' => [
            'driver' => 'stack',
            // `stdout` and `stderr` are our default log channels.
            // Third party logging tools will be enabled based on the corresponding
            // environment variables.
            'channels' => array_filter([
                'stdout',
                'stderr',
                env('LOG_SLACK_WEBHOOK_URL') ? 'slack' : null,
            ]),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'level' => env('LOG_LEVEL', 'debug'),
            'path' => storage_path('logs/laravel.log'),
        ],

        'daily' => [
            'driver' => 'daily',
            'level' => env('LOG_LEVEL', 'debug'),
            'path' => storage_path('logs/laravel.log'),
            'days' => env('LOG_DAILY_DAYS', 7),
        ],

        'slack' => [
            'driver' => 'slack',
            'level' => env('LOG_SLACK_LEVEL', 'critical'),
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', 'Laravel Log'),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
        ],

        // based on:
        // https://laravel-news.com/split-log-levels-between-stdout-and-stderr-with-laravel#content-configuring-laravel-to-filter-log-levels
        'stdout' => [
            'driver' => 'monolog',
            'handler' => FilterHandler::class,
            'with' => [
                'handler' => fn () => new StreamHandler('php://stdout'),
                'minLevelOrList' => max(Logger::toMonologLevel('debug'), Logger::toMonologLevel(env('LOG_LEVEL', 'debug'))),
                'maxLevel' => Logger::toMonologLevel('notice'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'handler' => FilterHandler::class,
            'with' => [
                'handler' => fn () => new StreamHandler('php://stderr'),
                'minLevelOrList' => max(Logger::toMonologLevel('warning'), Logger::toMonologLevel(env('LOG_LEVEL', 'debug'))),
                'maxLevel' => Logger::toMonologLevel('emergency'),
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],
    ],
];
