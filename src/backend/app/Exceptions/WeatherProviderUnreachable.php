<?php

namespace App\Exceptions;

/**
 * Thrown when the external weather data provider cannot be reached.
 */
class WeatherProviderUnreachable extends MpmException {
    protected int $httpStatusCode = 502;
}
