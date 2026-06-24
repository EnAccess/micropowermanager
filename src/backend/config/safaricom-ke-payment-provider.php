<?php

return [
    'api' => [
        'sandbox_url' => env('SAFARICOM_SANDBOX_URL', 'https://sandbox.safaricom.co.ke'),
        'production_url' => env('SAFARICOM_PRODUCTION_URL', 'https://api.safaricom.co.ke'),
    ],

    'sandbox' => [
        'shortcode' => env('SAFARICOM_SANDBOX_SHORTCODE', '174379'),
        'passkey' => env('SAFARICOM_SANDBOX_PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'),
    ],

    'transaction' => [
        'timeout' => env('SAFARICOM_TRANSACTION_TIMEOUT', 120),
        'max_amount' => env('SAFARICOM_MAX_AMOUNT', 150000),
        'min_amount' => env('SAFARICOM_MIN_AMOUNT', 1),
    ],
];
