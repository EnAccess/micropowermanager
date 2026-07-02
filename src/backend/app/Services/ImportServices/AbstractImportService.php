<?php

namespace App\Services\ImportServices;

use App\Exceptions\ImportFailedException;
use Illuminate\Support\Facades\Log;

abstract class AbstractImportService {
    /**
     * Import data from an array (typically from JSON export).
     *
     * @param array<string, mixed> $data
     *
     * @throws ImportFailedException if the input is invalid or the import transaction had to be rolled back
     */
    abstract public function import(array $data): ImportResult;

    /**
     * Validate the import data structure.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, string>
     */
    abstract public function validate(array $data): array;

    /**
     * @param array<string, string> $errors
     *
     * @throws ImportFailedException if $errors is non-empty
     */
    protected function assertValid(array $errors): void {
        if ($errors !== []) {
            throw new ImportFailedException($errors);
        }
    }

    /**
     * Logs the failure and throws, for use in the catch block wrapping an import transaction.
     *
     * @throws ImportFailedException always
     */
    protected function throwTransactionFailure(string $entityLabel, \Throwable $exception): never {
        Log::error("Error during {$entityLabel} import transaction", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        throw new ImportFailedException(['transaction' => "Failed to import {$entityLabel}: ".$exception->getMessage()]);
    }

    /**
     * Partition imported records into added and modified based on the 'action' key.
     *
     * @param array<int, array<string, mixed>> $imported
     *
     * @return array{added: list<array<string, mixed>>, modified: list<array<string, mixed>>}
     */
    protected function partitionResults(array $imported): array {
        return [
            'added' => array_values(array_filter($imported, fn (array $r): bool => ($r['action'] ?? '') === 'added')),
            'modified' => array_values(array_filter($imported, fn (array $r): bool => ($r['action'] ?? '') === 'modified')),
        ];
    }
}
