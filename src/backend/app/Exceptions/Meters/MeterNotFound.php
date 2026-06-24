<?php

namespace App\Exceptions\Meters;

use App\Exceptions\MpmException;

/**
 * Thrown when the requested meter cannot be found.
 */
class MeterNotFound extends MpmException {
    protected int $httpStatusCode = 404;
}
