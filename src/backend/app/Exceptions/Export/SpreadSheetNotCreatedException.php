<?php

namespace App\Exceptions\Export;

use App\Exceptions\MpmException;

/**
 * Thrown when a spreadsheet export file cannot be created.
 */
class SpreadSheetNotCreatedException extends MpmException {
    protected int $httpStatusCode = 500;
}
