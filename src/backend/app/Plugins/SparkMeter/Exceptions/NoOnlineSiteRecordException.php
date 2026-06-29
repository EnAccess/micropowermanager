<?php

namespace App\Plugins\SparkMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a transaction references a SparkMeter site that has no
 * corresponding online site record.
 */
class NoOnlineSiteRecordException extends MpmException {}
