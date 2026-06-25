<?php

namespace App\Exceptions;

/**
 * Thrown when the requested connection group does not exist.
 */
class ConnectionGroupNotFoundException extends MpmException {
    protected int $httpStatusCode = 404;
}
