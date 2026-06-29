<?php

namespace App\Plugins\SwiftaPaymentProvider\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when the amount in a Swifta transaction does not match the amount
 * originally recorded for it.
 */
class TransactionAmountDifferentException extends MpmException {}
