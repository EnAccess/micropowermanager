<?php

return [
    'pesapal_api_url_test' => env('PESAPAL_API_URL_TEST', 'https://cybqa.pesapal.com/pesapalv3'),
    'pesapal_api_url_live' => env('PESAPAL_API_URL_LIVE', 'https://pay.pesapal.com/v3'),
    'company_hash_salt' => env('PESAPAL_COMPANY_HASH_SALT', env('APP_KEY')),
    'api_timeout' => env('PESAPAL_API_TIMEOUT', 30),
    'token_cache_ttl_seconds' => env('PESAPAL_TOKEN_CACHE_TTL', 240),
    'currency' => [
        'default' => env('PESAPAL_DEFAULT_CURRENCY', 'KES'),
        'supported' => explode(',', env('PESAPAL_SUPPORTED_CURRENCIES', 'KES,UGX,TZS,USD')),
    ],
];
