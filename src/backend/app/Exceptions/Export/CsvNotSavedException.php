<?php

namespace App\Exceptions\Export;

class CsvNotSavedException extends \Exception {
    // $message (string) overriding property Exception::$message should not have a native type.
    /**
     * @var string
     */
    protected $message = 'CSV file could not be saved.';
}
