<?php

return [
    'webhooks_enabled' => env('WHATSAPP_WEBHOOKS_ENABLED', false),
    'webhook_max_bytes' => 1048576,
    'webhook_requests_per_minute' => 60,
];
