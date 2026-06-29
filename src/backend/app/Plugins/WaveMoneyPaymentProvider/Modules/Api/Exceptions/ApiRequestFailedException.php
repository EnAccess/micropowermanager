<?php

declare(strict_types=1);

namespace App\Plugins\WaveMoneyPaymentProvider\Modules\Api\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a request to the Wave Money API fails or returns a non-successful
 * HTTP status.
 */
class ApiRequestFailedException extends MpmException {
    protected int $httpStatusCode = 502;

    public function __construct(int $statusCode, string $uri, string $body) {
        $message = ['statusCode' => $statusCode, 'uri' => $uri, 'body' => $body];

        parent::__construct(json_encode($message));
    }
}
