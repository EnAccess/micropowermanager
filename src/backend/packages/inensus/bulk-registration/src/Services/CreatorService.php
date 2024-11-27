<?php

namespace Inensus\BulkRegistration\Services;

abstract class CreatorService implements ICreatorService {
    protected $model;

    public function __construct($model) {
        $this->model = $model;
    }

    public function createRelatedDataIfDoesNotExists($resolvedCsvData) {
        return $this->model->newQuery()->firstOrCreate($resolvedCsvData, $resolvedCsvData);
    }

    abstract public function resolveCsvDataFromComingRow($csvData);
}
