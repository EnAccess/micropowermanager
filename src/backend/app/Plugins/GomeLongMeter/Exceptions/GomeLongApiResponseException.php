<?php

namespace App\Plugins\GomeLongMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a call to the external GomeLong meter API fails or returns an error response.
 */
class GomeLongApiResponseException extends MpmException {
    protected int $httpStatusCode = 502;
}
