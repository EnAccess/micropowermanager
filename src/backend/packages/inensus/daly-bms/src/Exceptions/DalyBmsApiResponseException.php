<?php

namespace Inensus\DalyBms\Exceptions;

class DalyBmsApiResponseException extends \Exception {
    public function __construct($message) {
        parent::__construct($message);
    }
}
