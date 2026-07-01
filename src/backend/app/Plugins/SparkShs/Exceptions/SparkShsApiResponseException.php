<?php

namespace App\Plugins\SparkShs\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when the Spark SHS API returns an unexpected error response.
 */
class SparkShsApiResponseException extends MpmException {
    protected int $httpStatusCode = 502;
}
