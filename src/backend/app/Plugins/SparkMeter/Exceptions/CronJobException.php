<?php

namespace App\Plugins\SparkMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a SparkMeter scheduled (cron) job fails to complete.
 */
class CronJobException extends MpmException {
    protected int $httpStatusCode = 500;
}
