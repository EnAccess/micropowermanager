<?php

namespace Inensus\SunKingMeter\Exceptions;

class SunKingApiResponseException extends \Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}

