<?php

namespace App\Plugins\KelinMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when authenticating against the Kelin meter API fails.
 */
class KelinApiAuthenticationException extends MpmException {
    protected int $httpStatusCode = 502;
}
