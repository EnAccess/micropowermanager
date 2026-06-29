<?php

namespace App\Plugins\AngazaSHS\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when the Angaza API returns an error response or the request fails.
 */
class AngazaApiResponseException extends MpmException {
    protected int $httpStatusCode = 502;
}
