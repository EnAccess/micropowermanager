<?php

namespace Inensus\BulkRegistration\Services;

abstract class CreatorService implements ICreatorService {
    public function __construct(protected $model) {}

    public function createRelatedDataIfDoesNotExists($resolvedCsvData) {
        return $this->model->newQuery()->firstOrCreate($resolvedCsvData, $resolvedCsvData);
    }

    abstract public function resolveCsvDataFromComingRow($csvData);
}
