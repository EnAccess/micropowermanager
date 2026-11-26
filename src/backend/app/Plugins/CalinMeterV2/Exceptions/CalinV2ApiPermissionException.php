<?php

namespace App\Plugins\CalinMeterV2\Exceptions;

/**
 * Thrown when Calin refuses a call because the configured user lacks the role for
 * it. The fix differs from a transport failure: nothing in MPM is misconfigured,
 * the Calin account needs the role.
 */
class CalinV2ApiPermissionException extends CalinV2ApiResponseException {
    protected int $httpStatusCode = 403;
}
