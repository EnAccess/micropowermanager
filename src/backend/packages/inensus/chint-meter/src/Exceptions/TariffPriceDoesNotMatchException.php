<?php

namespace Inensus\ChintMeter\Exceptions;

class TariffPriceDoesNotMatchException extends \Exception {
    public function __construct($message) {
        parent::__construct($message);
    }
}
