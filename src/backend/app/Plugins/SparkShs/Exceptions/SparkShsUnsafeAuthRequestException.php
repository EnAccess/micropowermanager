<?php

namespace App\Plugins\SparkShs\Exceptions;

use App\Exceptions\MpmException;

/**
 * Exception thrown when an authentication request to Spark SHS is refused.
 *
 * This exception indicates that we refuse to execute the provided authentication
 * request to Spark SHS. Since the authentication and API URLs are provided by the user,
 * we perform sanity checks, such as preventing requests to interal IPs to mitigate
 * SSRF attacks.
 */
class SparkShsUnsafeAuthRequestException extends MpmException {
    protected int $httpStatusCode = 403;
}
