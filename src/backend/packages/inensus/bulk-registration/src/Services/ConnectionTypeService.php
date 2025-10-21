<?php

namespace Inensus\BulkRegistration\Services;

use App\Models\ConnectionType;

class ConnectionTypeService extends CreatorService {
    public function __construct(ConnectionType $connectionType) {
        parent::__construct($connectionType);
    }

    /**
     * @param array<string, mixed> $csvData
     */
    public function resolveCsvDataFromComingRow(array $csvData) {
        $connectionTypeConfig = config('bulk-registration.csv_fields.connection_type');
        $connectionTypeData = [
            'name' => $csvData[$connectionTypeConfig['name']],
        ];

        return $this->createRelatedDataIfDoesNotExists($connectionTypeData);
    }
}
