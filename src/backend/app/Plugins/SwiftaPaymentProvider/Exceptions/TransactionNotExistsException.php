<?php

namespace App\Plugins\SwiftaPaymentProvider\Exceptions;

/**
 * Thrown when a Swifta transaction referenced by a request cannot be found.
 *
 * Swifta names the offending field in the message, so this stays on the 400 the
 * plugin answers every other bad field with.
 */
class TransactionNotExistsException extends SwiftaException {}
