<?php

namespace App\Plugins\SwiftaPaymentProvider\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when an incoming Swifta request fails validation of its expected
 * fields or signature.
 */
class SwiftaValidationFailedException extends MpmException {}
