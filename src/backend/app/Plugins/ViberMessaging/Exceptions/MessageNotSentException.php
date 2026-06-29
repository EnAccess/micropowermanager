<?php

namespace App\Plugins\ViberMessaging\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when the Viber messaging service fails to deliver a message.
 */
class MessageNotSentException extends MpmException {
    protected int $httpStatusCode = 502;
}
