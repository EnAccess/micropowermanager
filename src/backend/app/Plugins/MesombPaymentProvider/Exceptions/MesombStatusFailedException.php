<?php

namespace App\Plugins\MesombPaymentProvider\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when the MeSomb provider reports a failed transaction status for a
 * payment.
 */
class MesombStatusFailedException extends MpmException {
    protected int $httpStatusCode = 502;
}
