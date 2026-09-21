<?php

namespace App\Plugins\FlutterwavePaymentProvider\Modules\Api\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a request to the Flutterwave API fails or returns an unexpected
 * response.
 */
class FlutterwaveApiException extends MpmException {
    protected int $httpStatusCode = 502;

    public function __construct(
        public private(set) int $statusCode,
        public private(set) string $uri,
        public private(set) string $responseBody,
        string $message = '',
        int $code = 0,
        ?\Exception $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
