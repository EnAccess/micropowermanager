<?php

namespace App\Plugins\CalinMeterV2\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a call to a Calin API V2 system fails or returns an error response.
 */
class CalinV2ApiResponseException extends MpmException {
    protected int $httpStatusCode = 502;
}
