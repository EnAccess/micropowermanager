<?php

namespace App\Plugins\MesombPaymentProvider\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when no customer can be found for the phone number supplied in a
 * MeSomb payment request.
 */
class MesombPaymentPhoneNumberNotFoundException extends MpmException {
    protected int $httpStatusCode = 404;
}
