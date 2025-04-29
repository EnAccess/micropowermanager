<?php

namespace App\Exceptions\Export;

class CsvNotSavedException extends \Exception {
    protected $message = 'CSV file could not be saved.';
}
