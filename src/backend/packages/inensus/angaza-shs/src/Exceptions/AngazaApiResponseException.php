<?php

namespace Inensus\AngazaSHS\Exceptions;

class AngazaApiResponseException extends \Exception {
    public function __construct($message) {
        parent::__construct($message);
    }
}
