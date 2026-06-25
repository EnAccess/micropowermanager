<?php

namespace App\Plugins\ViberMessaging\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when registering a webhook with the Viber messaging service fails.
 */
class WebhookNotCreatedException extends MpmException {
    protected int $httpStatusCode = 502;
}
