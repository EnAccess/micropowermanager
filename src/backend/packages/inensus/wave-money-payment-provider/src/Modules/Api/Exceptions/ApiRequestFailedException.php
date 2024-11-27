<?php

declare(strict_types=1);

namespace Inensus\WaveMoneyPaymentProvider\Modules\Api\Exceptions;

class ApiRequestFailedException extends \Exception {
    public function __construct(int $statusCode, string $uri, string $body) {
        $message = ['statusCode' => $statusCode, 'uri' => $uri, 'body' => $body];

        parent::__construct(json_encode($message));
    }
}
