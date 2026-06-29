<?php

namespace App\Exceptions;

/**
 * Thrown when fetching transaction data from an external provider fails.
 */
class TransactionFetchingException extends MpmException {
    protected int $httpStatusCode = 502;
}
