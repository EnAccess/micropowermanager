<?php

namespace App\Plugins\BulkRegistration\Exceptions;

/**
 * Thrown during bulk registration when the specified manufacturer is not
 * supported.
 */
class ManufacturerNotSupportedException extends MissingDataException {}
