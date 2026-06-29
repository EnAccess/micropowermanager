<?php

namespace App\Plugins\SteamaMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a Steama meter scheduled (cron) job fails to complete.
 */
class CronJobException extends MpmException {
    protected int $httpStatusCode = 500;
}
