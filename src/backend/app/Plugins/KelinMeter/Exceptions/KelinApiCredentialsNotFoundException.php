<?php

namespace App\Plugins\KelinMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when no Kelin meter API credentials are configured for the request.
 */
class KelinApiCredentialsNotFoundException extends MpmException {}
