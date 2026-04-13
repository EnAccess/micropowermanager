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

    /**
     * Partition imported records into added and modified based on the 'action' key.
     *
     * @param array<int, array<string, mixed>> $imported
     *
     * @return array{added: array<int, array<string, mixed>>, modified: array<int, array<string, mixed>>, added_count: int, modified_count: int}
     */
    protected function partitionResults(array $imported): array {
        $added = array_values(array_filter($imported, fn (array $r): bool => ($r['action'] ?? '') === 'added'));
        $modified = array_values(array_filter($imported, fn (array $r): bool => ($r['action'] ?? '') === 'modified'));

        return [
            'added' => $added,
            'modified' => $modified,
            'added_count' => count($added),
            'modified_count' => count($modified),
        ];
    }
}
