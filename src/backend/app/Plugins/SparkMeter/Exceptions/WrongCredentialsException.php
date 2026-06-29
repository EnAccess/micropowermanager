<?php

namespace App\Plugins\SparkMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when the configured SparkMeter API credentials are invalid.
 */
class WrongCredentialsException extends MpmException {}
