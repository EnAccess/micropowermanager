<?php

return [
    'flutterwave_api_url' => 'https://api.flutterwave.com/v3',
    'company_hash_salt' => env('FLUTTERWAVE_COMPANY_HASH_SALT', env('APP_KEY')),
    'api_timeout' => env('FLUTTERWAVE_API_TIMEOUT', 30),
    'verify_webhook_signature' => env('FLUTTERWAVE_VERIFY_WEBHOOK_SIGNATURE', true),
    'currency' => [
        'default' => env('FLUTTERWAVE_DEFAULT_CURRENCY', 'NGN'),
        'supported' => explode(',', env('FLUTTERWAVE_SUPPORTED_CURRENCIES', 'NGN,GHS,KES,ZAR,USD')),
    ],
];
