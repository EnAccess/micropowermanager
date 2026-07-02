<?php

namespace App\Services\ImportServices;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Outcome of an import run that was processed (fully or partially) — per-item
 * failures are reported here, while whole-request failures (invalid input, a
 * rolled-back transaction) throw {@see \App\Exceptions\ImportFailedException}.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class ImportResult implements Arrayable {
    /**
     * @param list<array<string, mixed>> $added
     * @param list<array<string, mixed>> $modified
     * @param list<array<string, mixed>> $failed
     */
    public function __construct(
        public string $message,
        public array $added,
        public array $modified,
        public array $failed,
    ) {}

    public function importedCount(): int {
        return count($this->added) + count($this->modified);
    }

    public function success(): bool {
        return !($this->importedCount() === 0 && $this->failed !== []);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array {
        return [
            'success' => $this->success(),
            'message' => $this->message,
            'imported_count' => $this->importedCount(),
            'added_count' => count($this->added),
            'modified_count' => count($this->modified),
            'failed_count' => count($this->failed),
            'added' => $this->added,
            'modified' => $this->modified,
            'failed' => $this->failed,
        ];
    }
}
