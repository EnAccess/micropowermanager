<?php

namespace Inensus\GomeLongMeter\Exceptions;

class GomeLongApiResponseException extends \Exception {
    public function __construct($message) {
        parent::__construct($message);
    }
}
