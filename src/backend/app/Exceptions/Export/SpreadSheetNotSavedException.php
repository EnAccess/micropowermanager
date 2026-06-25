<?php

namespace App\Exceptions\Export;

use App\Exceptions\MpmException;

/**
 * Thrown when a generated spreadsheet export file cannot be saved to storage.
 */
class SpreadSheetNotSavedException extends MpmException {
    protected int $httpStatusCode = 500;
}
