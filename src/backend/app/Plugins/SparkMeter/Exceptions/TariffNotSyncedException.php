<?php

namespace App\Plugins\SparkMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a customer's SparkMeter tariff has not yet been synced into MPM,
 * so the customer's meter cannot be created without misassigning its tariff.
 */
class TariffNotSyncedException extends MpmException {}
