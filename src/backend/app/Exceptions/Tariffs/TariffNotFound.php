<?php

namespace App\Exceptions\Tariffs;

use App\Exceptions\MpmException;

/**
 * Thrown when the requested tariff cannot be found.
 */
class TariffNotFound extends MpmException {
    protected int $httpStatusCode = 404;
}
