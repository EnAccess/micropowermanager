<?php

namespace Inensus\MicroStarMeter\Exceptions;

class MicroStarApiResponseException extends \Exception {
    public function __construct(string $responseMessage) {
        $errorMessage = "MicroStar Meter Api Response Error: $responseMessage";

        parent::__construct($errorMessage);
    }
}
