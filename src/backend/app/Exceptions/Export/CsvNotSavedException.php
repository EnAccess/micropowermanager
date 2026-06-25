<?php

namespace App\Exceptions\Export;

use App\Exceptions\MpmException;

/**
 * Thrown when a generated CSV export file cannot be saved to storage.
 */
class CsvNotSavedException extends MpmException {
    protected int $httpStatusCode = 500;

    // $message (string) overriding property Exception::$message should not have a native type.
    /**
     * @var string
     */
    protected $message = 'CSV file could not be saved.';
}
