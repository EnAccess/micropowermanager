<?php

namespace App\Plugins\CalinSmartMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a call to the external Calin Smart meter API fails or returns an error response.
 */
class CalinSmartApiResponseException extends MpmException {
    protected int $httpStatusCode = 502;
}
