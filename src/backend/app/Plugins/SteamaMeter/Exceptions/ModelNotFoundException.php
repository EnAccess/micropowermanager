<?php

namespace App\Plugins\SteamaMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when an expected Steama meter model or its credentials cannot be found.
 */
class ModelNotFoundException extends MpmException {
    protected int $httpStatusCode = 404;
}
