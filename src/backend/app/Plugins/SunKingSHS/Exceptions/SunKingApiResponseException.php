<?php

namespace App\Plugins\SunKingSHS\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a call to the external SunKing solar home system API fails or returns an error response.
 */
class SunKingApiResponseException extends MpmException {
    protected int $httpStatusCode = 502;
}
