<?php

namespace App\Plugins\SparkMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when no SparkMeter API credentials are configured for the request.
 */
class CredentialsNotFoundException extends MpmException {}
