<?php

namespace App\Plugins\OpenPaygo\Exceptions;

use App\Exceptions\MpmException;

class OpenPaygoGeneratorException extends MpmException {
    protected int $httpStatusCode = 502;
}
