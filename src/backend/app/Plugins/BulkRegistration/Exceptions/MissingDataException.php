<?php

namespace App\Plugins\BulkRegistration\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown during bulk registration when required input data is missing or
 * cannot be resolved.
 */
class MissingDataException extends MpmException {}
