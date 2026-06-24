<?php

namespace App\Exceptions\AccessRates;

use App\Exceptions\MpmException;

/**
 * Thrown when no access rate is found for the given tariff or connection.
 */
class NoAccessRateFound extends MpmException {
    protected int $httpStatusCode = 404;
}
