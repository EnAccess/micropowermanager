<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Safaricom M-PESA API Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration settings for the Safaricom M-PESA API
    | integration. These settings are used to authenticate and interact with
    | the Safaricom M-PESA API.
    |
    */

    'api' => [
        'base_url' => env('SAFARICOM_API_BASE_URL', 'https://sandbox.safaricom.co.ke'),
        'consumer_key' => env('SAFARICOM_CONSUMER_KEY', ''),
        'consumer_secret' => env('SAFARICOM_CONSUMER_SECRET', ''),
        'passkey' => env('SAFARICOM_PASSKEY', ''),
        'shortcode' => env('SAFARICOM_SHORTCODE', ''),
        'env' => env('SAFARICOM_ENV', 'sandbox'), // sandbox or production
    ],

    'webhook' => [
        'validation_url' => env('SAFARICOM_VALIDATION_URL', ''),
        'confirmation_url' => env('SAFARICOM_CONFIRMATION_URL', ''),
        'timeout_url' => env('SAFARICOM_TIMEOUT_URL', ''),
        'result_url' => env('SAFARICOM_RESULT_URL', ''),
    ],

    'transaction' => [
        'timeout' => env('SAFARICOM_TRANSACTION_TIMEOUT', 60), // in seconds
        'max_amount' => env('SAFARICOM_MAX_AMOUNT', 150000), // in KES
        'min_amount' => env('SAFARICOM_MIN_AMOUNT', 1), // in KES
    ],
];
