<?php

namespace Inensus\SunKingSHS\Exceptions;

class SunKingApiResponseException extends \Exception {
    public function __construct($message) {
        parent::__construct($message);
    }
}
