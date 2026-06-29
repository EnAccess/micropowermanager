<?php

namespace App\Exceptions\Meters;

use App\Exceptions\MpmException;

/**
 * Thrown when an operation requires a meter to be in use but it is not.
 */
class MeterIsNotInUse extends MpmException {}
