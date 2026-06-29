<?php

namespace App\Plugins\SparkMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when the stored SparkMeter credentials are stale and must be refreshed
 * before a transaction can be processed.
 */
class CredentialsNotUpToDateException extends MpmException {}
