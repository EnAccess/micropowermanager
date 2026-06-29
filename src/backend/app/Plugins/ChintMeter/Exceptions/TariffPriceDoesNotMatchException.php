<?php

namespace App\Plugins\ChintMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when the tariff price configured locally does not match the price registered on the Chint meter.
 */
class TariffPriceDoesNotMatchException extends MpmException {}
