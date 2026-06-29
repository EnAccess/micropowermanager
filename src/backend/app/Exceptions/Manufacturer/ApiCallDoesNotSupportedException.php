<?php

namespace App\Exceptions\Manufacturer;

use App\Exceptions\MpmException;

/**
 * Thrown when a manufacturer API does not support the requested call.
 */
class ApiCallDoesNotSupportedException extends MpmException {}
