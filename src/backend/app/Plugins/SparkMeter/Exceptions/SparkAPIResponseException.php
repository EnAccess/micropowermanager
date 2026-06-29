<?php

namespace App\Plugins\SparkMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when the SparkMeter API returns an unexpected or error response.
 */
class SparkAPIResponseException extends MpmException {
    protected int $httpStatusCode = 502;
}
