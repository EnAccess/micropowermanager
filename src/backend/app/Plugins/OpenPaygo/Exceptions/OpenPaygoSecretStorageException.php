<?php

namespace App\Plugins\OpenPaygo\Exceptions;

use App\Exceptions\MpmException;

class OpenPaygoSecretStorageException extends MpmException {
    protected int $httpStatusCode = 500;
}
