<?php

namespace App\Exceptions;

/**
 * Thrown when the requested SMS type cannot be resolved.
 */
class SmsTypeNotFoundException extends MpmException {
    protected int $httpStatusCode = 404;
}
