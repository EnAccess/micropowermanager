<?php

namespace App\Plugins\KelinMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when the Kelin meter API returns a null or empty data payload.
 */
class KelinApiEmtyDataException extends MpmException {
    protected int $httpStatusCode = 502;
}
