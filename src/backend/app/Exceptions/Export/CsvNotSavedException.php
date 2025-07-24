<?php

namespace App\Exceptions\Export;

class CsvNotSavedException extends \Exception {
    protected string $message = 'CSV file could not be saved.';
}
