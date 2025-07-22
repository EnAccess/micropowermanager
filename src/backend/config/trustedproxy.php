<?php

return [
    // Use this to set the trusted proxies for the application.
    // This ensures laravel correctly identifies the client's IP address and resolve HTTPS links.
    // https://github.com/laravel/framework/blob/11.x/src/Illuminate/Http/Middleware/TrustProxies.php#L67-L86
    'proxies' => env('TRUSTEDPROXY_PROXIES'),
];
