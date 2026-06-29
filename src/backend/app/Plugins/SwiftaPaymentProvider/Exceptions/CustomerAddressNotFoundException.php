<?php

namespace App\Plugins\SwiftaPaymentProvider\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when no address can be found for the customer referenced in a Swifta
 * transaction.
 */
class CustomerAddressNotFoundException extends MpmException {
    protected int $httpStatusCode = 404;
}
