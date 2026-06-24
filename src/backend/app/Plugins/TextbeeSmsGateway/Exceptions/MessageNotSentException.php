<?php

namespace App\Plugins\TextbeeSmsGateway\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when the Textbee SMS gateway fails to deliver an SMS message.
 */
class MessageNotSentException extends MpmException {
    protected int $httpStatusCode = 502;
}
