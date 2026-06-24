<?php

namespace App\Plugins\KelinMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a Kelin meter scheduled (cron) job fails to complete.
 */
class CronJobException extends MpmException {
    protected int $httpStatusCode = 500;
}
