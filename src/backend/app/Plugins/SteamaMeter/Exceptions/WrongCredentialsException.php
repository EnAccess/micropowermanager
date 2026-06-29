<?php

namespace App\Plugins\SteamaMeter\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when the configured Steama meter API credentials are invalid.
 */
class WrongCredentialsException extends MpmException {}
