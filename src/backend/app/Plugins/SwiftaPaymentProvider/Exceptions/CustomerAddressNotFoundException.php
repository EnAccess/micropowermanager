<?php

namespace App\Plugins\SwiftaPaymentProvider\Exceptions;

/**
 * Thrown when no address can be found for the customer referenced in a Swifta
 * transaction.
 */
class CustomerAddressNotFoundException extends SwiftaException {
    protected int $httpStatusCode = 404;
}
