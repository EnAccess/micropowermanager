<?php

namespace App\Services\ImportServices;

use App\Exceptions\ImportFailedException;
use Illuminate\Support\Facades\Log;

abstract class AbstractImportService {
    /**
     * Import a list of items (the `data` list of a JSON export file, validated
     * by the endpoint's FormRequest).
     *
     * @param list<array<string, mixed>> $data
     *
     * @throws ImportFailedException if the import transaction had to be rolled back
     */
    abstract public function import(array $data): ImportResult;

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
