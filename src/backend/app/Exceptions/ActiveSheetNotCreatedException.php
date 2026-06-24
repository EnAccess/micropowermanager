<?php

namespace App\Exceptions;

/**
 * Thrown when the active sheet of a spreadsheet cannot be created during export.
 */
class ActiveSheetNotCreatedException extends MpmException {
    protected int $httpStatusCode = 500;
}
