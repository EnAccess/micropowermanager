<?php

namespace Inensus\PaystackPaymentProvider\Modules\Api\Exceptions;

class PaystackApiException extends \Exception {
    public function __construct(
        private int $statusCode,
        private string $uri,
        private string $responseBody,
        string $message = '',
        int $code = 0,
        ?\Exception $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int {
        return $this->statusCode;
    }

    public function getUri(): string {
        return $this->uri;
    }

    public function getResponseBody(): string {
        return $this->responseBody;
    }
}
