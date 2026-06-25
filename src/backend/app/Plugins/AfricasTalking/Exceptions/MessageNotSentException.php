<?php

namespace App\Plugins\AfricasTalking\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when the Africa's Talking gateway fails to deliver an SMS message.
 */
class MessageNotSentException extends MpmException {
    protected int $httpStatusCode = 502;
}
