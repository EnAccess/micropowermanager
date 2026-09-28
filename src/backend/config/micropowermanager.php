<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Load MicroPowerManager demo data
    |--------------------------------------------------------------------------
    |
    | Whether or not the demo data should be loaded when the MicroPowerManager
    | starts for the first time.
    |
    | Recommended for local development and demo environments.
    |
    | Supported: true, false (default)
    |
    */

    'load_demo_data' => env('MPM_LOAD_DEMO_DATA', false),

    /*
    |--------------------------------------------------------------------------
    | Operator dashboard
    |--------------------------------------------------------------------------
    |
    | The platform-host dashboard that aggregates every tenant. It is served
    | from a nightly rebuilt cache, so nothing here is read on the hot path of
    | a tenant request.
    |
    | The dashboard is opened only occasionally, so the cache never expires: a
    | missed or failed nightly run leaves the last snapshot in place, and its
    | age is communicated through `generated_at` and the `stale` flag.
    |
    */

    'operator_dashboard' => [
        'basic_auth' => [
            'username' => env('OPERATOR_DASHBOARD_USER'),
            'password' => env('OPERATOR_DASHBOARD_PASSWORD'),
        ],

        'refreshing_ttl_minutes' => env('OPERATOR_DASHBOARD_REFRESHING_TTL_MINUTES', 15),
        'stale_after_hours' => env('OPERATOR_DASHBOARD_STALE_AFTER_HOURS', 36),

        'series_months' => 12,
        'activity_entries' => 5,

        // Days since the last transaction that still count as healthy.
        'health' => [
            'active_days' => 7,
            'watch_days' => 21,
        ],
    ],
];
