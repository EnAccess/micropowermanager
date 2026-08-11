<?php

namespace App\Plugins\SwiftaPaymentProvider\Exceptions;

/**
 * Thrown when an incoming Swifta request fails validation of its expected
 * fields or signature.
 */
class SwiftaValidationFailedException extends SwiftaException {}
