<?php

namespace Inensus\ChintMeter\Exceptions;

class ChintApiResponseException extends \Exception {
    public function __construct($message) {
        parent::__construct($message);
    }
}
