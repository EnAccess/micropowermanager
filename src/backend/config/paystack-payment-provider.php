<?php

return [
    'paystack_api_url' => 'https://api.paystack.co',
    'company_hash_salt' => env('PAYSTACK_COMPANY_HASH_SALT', env('APP_KEY')),
    'api_timeout' => env('PAYSTACK_API_TIMEOUT', 30),
    'verify_webhook_signature' => env('PAYSTACK_VERIFY_WEBHOOK_SIGNATURE', true),
    'currency' => [
        'default' => env('PAYSTACK_DEFAULT_CURRENCY', 'NGN'),
        'supported' => explode(',', env('PAYSTACK_SUPPORTED_CURRENCIES', 'NGN,GHS,KES,ZAR')),
    ],
];
