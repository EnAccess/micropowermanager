<?php

namespace App\Exceptions\Manufacturer;

use App\Exceptions\MpmException;

/**
 * Thrown when a meter cannot be read through the manufacturer integration.
 */
class MeterIsNotReadable extends MpmException {}
