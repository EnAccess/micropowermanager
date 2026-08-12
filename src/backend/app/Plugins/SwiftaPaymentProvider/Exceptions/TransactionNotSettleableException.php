<?php

namespace App\Plugins\SwiftaPaymentProvider\Exceptions;

/**
 * Thrown when a Swifta callback is valid but MPM cannot settle it, because the
 * transaction it names is missing the provider record the payment hangs off.
 *
 * That is a fault in our own data rather than in Swifta's request, so it answers
 * with a 5xx: the callback is worth retrying once the record is repaired.
 */
class TransactionNotSettleableException extends SwiftaException {
    protected int $httpStatusCode = 500;
}
