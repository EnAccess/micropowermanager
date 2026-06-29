<?php

namespace App\Plugins\CalinSmartMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when the Calin Smart meter API credentials are not configured for the tenant.
 */
class CalinSmartCreadentialsNotFoundException extends MpmException {}
