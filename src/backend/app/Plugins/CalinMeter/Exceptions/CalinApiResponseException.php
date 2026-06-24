<?php

namespace App\Plugins\CalinMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a call to the external Calin meter API fails or returns an error response.
 */
class CalinApiResponseException extends MpmException {
    protected int $httpStatusCode = 502;
}
