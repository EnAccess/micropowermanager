<?php

namespace App\Plugins\KelinMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when the Kelin meter API returns an unexpected or error response.
 */
class KelinApiResponseException extends MpmException {
    protected int $httpStatusCode = 502;
}
