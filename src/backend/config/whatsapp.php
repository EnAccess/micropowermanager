<?php

return [
    'webhooks_enabled' => env('WHATSAPP_WEBHOOKS_ENABLED', false),
    'webhook_max_bytes' => 1048576,
    'webhook_requests_per_minute' => 60,
    'sms_fallback' => [
        // WhatsApp is the primary channel; the SMS fallback only fires once a
        // message is proven failed or has stalled past the delivery timeout.
        'enabled' => env('WHATSAPP_SMS_FALLBACK_ENABLED', true),
        'timeout_minutes' => env('WHATSAPP_SMS_FALLBACK_TIMEOUT_MINUTES', 5),
        'max_attempts' => env('WHATSAPP_SMS_FALLBACK_MAX_ATTEMPTS', 3),
    ],
];
