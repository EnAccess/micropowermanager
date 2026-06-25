<?php

namespace App\Plugins\SparkMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when the locally stored SparkMeter sites are stale and must be synced
 * before a transaction can be processed.
 */
class SitesNotUpToDateException extends MpmException {}
