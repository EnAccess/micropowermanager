<?php

namespace App\Plugins\SteamaMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when the Steama meter API returns an unexpected or error response.
 */
class SteamaApiResponseException extends MpmException {
    protected int $httpStatusCode = 502;
}
