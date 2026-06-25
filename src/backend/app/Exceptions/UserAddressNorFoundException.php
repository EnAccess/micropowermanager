<?php

namespace App\Exceptions;

/**
 * Thrown when the address associated with a user cannot be found.
 */
class UserAddressNorFoundException extends MpmException {
    protected int $httpStatusCode = 404;
}
