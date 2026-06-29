<?php

namespace App\Exceptions;

/**
 * Thrown when an API user cannot be found for the given credentials or identifier.
 */
class ApiUserNotFound extends MpmException {
    protected int $httpStatusCode = 404;
}
