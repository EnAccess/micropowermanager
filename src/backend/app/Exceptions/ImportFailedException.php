<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Thrown when an import request cannot be processed at all — either the
 * input failed validation, or the transaction wrapping the import had to be
 * rolled back. Per-item failures within an otherwise-successful batch are
 * not an error; see {@see \App\Services\ImportServices\ImportResult}.
 *
 * Overrides {@see MpmException::render()} to keep the response body import
 * clients already depend on ({"success": false, "errors": {...}}) instead of
 * the base class's {"message": ...} shape.
 */
class ImportFailedException extends MpmException {
    /**
     * @param array<string, string> $errors
     */
    public function __construct(
        private readonly array $errors,
        string $message = 'Import failed',
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array {
        return $this->errors;
    }

    public function render(Request $request): JsonResponse {
        return response()->json(['success' => false, 'errors' => $this->errors], $this->httpStatusCode);
    }
}
