<?php

namespace App\Plugins\CalinMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a call to the external Calin meter API fails or returns an error response.
 */
class CalinApiResponseException extends MpmException {
    protected int $httpStatusCode = 502;

    public function __construct(
        string $message,
        public readonly ?int $resultCode = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
