<?php

namespace App\Plugins\MicroStarMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a call to the external MicroStar meter API fails or returns an error response.
 */
class MicroStarApiResponseException extends MpmException {
    protected int $httpStatusCode = 502;

    public function __construct(string $responseMessage) {
        $errorMessage = "MicroStar Meter Api Response Error: $responseMessage";

        parent::__construct($errorMessage);
    }
}
