<?php

namespace App\Plugins\VodacomMzPaymentProvider\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when the Vodacom MZ API returns an error response or a payment is
 * rejected by the provider.
 */
class VodacomMzApiResponseException extends MpmException {
    protected int $httpStatusCode = 502;
}
