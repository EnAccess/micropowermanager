<?php

namespace App\Plugins\StsMeterVend\Exceptions;

use App\Exceptions\MpmException;

class StsApiResponseException extends MpmException {
    protected int $httpStatusCode = 502;

    public function __construct(string $message, public readonly ?string $field = null) {
        parent::__construct($message);
    }
}
