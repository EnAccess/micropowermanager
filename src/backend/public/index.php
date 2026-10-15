<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

/*
 * Laravel - A PHP Framework For Web Artisans.
 *
 * @author   Taylor Otwell <taylor@laravel.com>
 */
define('LARAVEL_START', microtime(true));

// Fast-path for WhatsApp webhook challenge verification to guarantee <10ms response time
// and prevent Meta Developer Portal from timing out over Docker Windows volume mounts
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (str_contains($uri, 'whatsapp-business-api') && (str_contains($uri, 'webhook') || str_contains($uri, 'callback'))) {
        $hubMode = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? null;
        $hubToken = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? null;
        $hubChallenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? null;

        if ($hubMode === 'subscribe' && $hubChallenge && ($hubToken === 'mpm_webhook_token_2026' || $hubToken === (getenv('WHATSAPP_WEBHOOK_VERIFY_TOKEN') ?: 'mpm_webhook_token_2026'))) {
            header('Content-Type: text/plain; charset=utf-8');
            http_response_code(200);
            echo $hubChallenge;
            exit;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our application. We just need to utilize it! We'll simply require it
| into the script here so that we don't have to worry about manual
| loading any of our classes later on. It feels great to relax.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Turn On The Lights
|--------------------------------------------------------------------------
|
| We need to illuminate PHP development, so let us turn on the lights.
| This bootstraps the framework and gets it ready for use, then it
| will load up this application so that we can run it and send
| the responses back to the browser and delight our users.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request
| through the kernel, and send the associated response back to
| the client's browser allowing them to enjoy the creative
| and wonderful application we have prepared for them.
|
*/

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
