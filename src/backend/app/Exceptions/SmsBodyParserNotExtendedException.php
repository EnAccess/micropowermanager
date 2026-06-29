<?php

namespace App\Exceptions;

/**
 * Thrown when an SMS body parser does not extend the expected base parser class.
 */
class SmsBodyParserNotExtendedException extends MpmException {
    protected int $httpStatusCode = 500;
}
