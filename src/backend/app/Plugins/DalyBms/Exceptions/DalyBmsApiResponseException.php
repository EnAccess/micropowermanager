<?php

namespace App\Plugins\DalyBms\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a call to the external Daly BMS API fails or returns an error response.
 */
class DalyBmsApiResponseException extends MpmException {
    protected int $httpStatusCode = 502;
}
