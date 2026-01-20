<?php

namespace App\Plugins\BulkRegistration\Services;

abstract class CreatorService {
    public function __construct(protected object $model) {}

    /**
     * @param array<string, mixed>|list<array<string, mixed>> $resolvedCsvData
     *
     * @return object|void
     */
    public function createRelatedDataIfDoesNotExists(array $resolvedCsvData) {
        return $this->model->newQuery()->firstOrCreate($resolvedCsvData, $resolvedCsvData);
    }

    /**
     * @param array<string, mixed> $csvData
     *
     * @return object|void|bool
     */
    abstract public function resolveCsvDataFromComingRow(array $csvData);
}
