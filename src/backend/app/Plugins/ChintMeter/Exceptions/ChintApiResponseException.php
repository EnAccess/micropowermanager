<?php

namespace App\Plugins\ChintMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a call to the external Chint meter API fails or returns an error response.
 */
class ChintApiResponseException extends MpmException {
    protected int $httpStatusCode = 502;
}
