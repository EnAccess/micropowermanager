<?php

namespace App\Plugins\BulkRegistration\Helpers;

use ParseCsv\Csv;

class CsvFileParser {
    private Csv $csv;

    public function __construct() {
        $this->csv = new Csv();
    }

    /**
     * @return array<string|int, mixed>
     */
    public function parseCsvFromFilePath(string $path) {
        $this->csv->auto($path);

        return $this->csv->data;
    }
}
