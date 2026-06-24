<?php

namespace App\Exceptions;

/**
 * Thrown when the requested SMS record cannot be found.
 */
class SmsRecordNotFoundException extends MpmException {
    protected int $httpStatusCode = 404;
}
