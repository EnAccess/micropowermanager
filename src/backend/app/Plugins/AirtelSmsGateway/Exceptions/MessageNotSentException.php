<?php

namespace App\Plugins\AirtelSmsGateway\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when an SMS could not be handed over to the Airtel SMSC.
 */
class MessageNotSentException extends MpmException {
    protected int $httpStatusCode = 502;
}
