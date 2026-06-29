<?php

namespace App\Plugins\SwiftaPaymentProvider\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a Swifta transaction referenced by a request cannot be found.
 */
class TransactionNotExistsException extends MpmException {
    protected int $httpStatusCode = 404;
}
