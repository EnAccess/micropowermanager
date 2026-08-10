<?php

namespace App\Exceptions\Device;

use App\Exceptions\MpmException;

/**
 * Thrown when a credit amount cannot be priced for a device — a meter without a
 * tariff, or a unit with no appliance plan to read a daily price from.
 */
class CreditPriceNotFoundException extends MpmException {}
