<?php

namespace App\Plugins\WhatsApp\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when the WhatsApp Business API fails or credentials are misconfigured.
 */
class WhatsAppApiException extends MpmException {
    protected int $httpStatusCode = 502;
}
