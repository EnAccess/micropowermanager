<?php

namespace App\Plugins\AirtelPaymentProvider\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a call from Airtel cannot be accepted. The message is sent back to Airtel.
 */
class AirtelRequestException extends MpmException {
    protected int $httpStatusCode = 400;
}
