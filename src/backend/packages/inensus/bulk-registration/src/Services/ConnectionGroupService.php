<?php

namespace Inensus\BulkRegistration\Services;

use App\Models\ConnectionGroup;

class ConnectionGroupService extends CreatorService {
    public function __construct(ConnectionGroup $connectionGroup) {
        parent::__construct($connectionGroup);
    }

    public function resolveCsvDataFromComingRow($csvData) {
        $connectionGroupConfig = config('bulk-registration.csv_fields.connection_group');
        $connectionGroupData = [
            'name' => $csvData[$connectionGroupConfig['name']],
        ];

        return $this->createRelatedDataIfDoesNotExists($connectionGroupData);
    }
}
