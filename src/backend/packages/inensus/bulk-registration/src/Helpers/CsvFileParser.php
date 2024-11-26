<?php

namespace Inensus\BulkRegistration\Helpers;

use ParseCsv\Csv;

class CsvFileParser {
    private $csv;

    public function __construct() {
        $this->csv = new Csv();
    }

    public function parseCsvFromFilePath($path) {
        $this->csv->auto($path);

        return $this->csv->data;
    }
}
