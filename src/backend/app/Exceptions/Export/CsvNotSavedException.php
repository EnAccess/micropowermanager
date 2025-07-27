<?php

namespace App\Exceptions\Export;

class CsvNotSavedException extends \Exception {
    /** @var string */
    protected $message = 'CSV file could not be saved.';
}
