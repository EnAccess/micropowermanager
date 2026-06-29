<?php

namespace App\Plugins\PesapalPaymentProvider\Modules\Api\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a request to the Pesapal API fails or returns an unexpected
 * response.
 */
class PesapalApiException extends MpmException {
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
