<?php

namespace App\Plugins\EgoSmsGateway\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when EgoSMS does not accept an SMS message.
 */
class MessageNotSentException extends MpmException {
    protected int $httpStatusCode = 502;
}
