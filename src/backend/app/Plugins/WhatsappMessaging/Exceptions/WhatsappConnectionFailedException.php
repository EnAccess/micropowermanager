<?php

namespace App\Plugins\WhatsappMessaging\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when the WhatsApp Cloud API cannot be reached or rejects the operator's credentials.
 */
class WhatsappConnectionFailedException extends MpmException {
    protected int $httpStatusCode = 502;
}
