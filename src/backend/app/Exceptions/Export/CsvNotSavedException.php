<?php

namespace App\Exceptions;

class CsvNotSavedException extends \Exception {
    protected $message = 'CSV file could not be saved.';
}
