<?php

namespace App\Plugins\PesapalPaymentProvider\Modules\Api\Exceptions;

class PesapalApiException extends \Exception {
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
