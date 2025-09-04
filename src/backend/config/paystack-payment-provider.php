<?php

return [
    'paystack_api_url' => env('PAYSTACK_API_URL', 'https://api.paystack.co'),
    'merchant_email' => env('PAYSTACK_MERCHANT_EMAIL', 'noreply@micropowermanager.com'),
    'company_hash_salt' => env('PAYSTACK_COMPANY_HASH_SALT', env('APP_KEY')),
    'api_timeout' => env('PAYSTACK_API_TIMEOUT', 30),
    'verify_webhook_signature' => env('PAYSTACK_VERIFY_WEBHOOK_SIGNATURE', true),
];
