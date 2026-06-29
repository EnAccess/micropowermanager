<?php

namespace App\Plugins\StronMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a call to the external Stron meter API fails or returns an error response.
 */
class StronApiResponseException extends MpmException {
    protected int $httpStatusCode = 502;
}
