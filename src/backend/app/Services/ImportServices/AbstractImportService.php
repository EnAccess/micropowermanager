<?php

namespace App\Services\ImportServices;

abstract class AbstractImportService {
    /**
     * Import data from an array (typically from JSON export).
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    abstract public function import(array $data): array;

    /**
     * Validate the import data structure.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, string>
     */
    abstract public function validate(array $data): array;
}
