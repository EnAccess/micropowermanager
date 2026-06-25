<?php

namespace App\Plugins\GomeLongMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a scheduled GomeLong meter job fails during execution.
 */
class CronJobException extends MpmException {
    protected int $httpStatusCode = 500;
}
