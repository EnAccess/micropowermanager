<?php

namespace MPM\CustomBulkRegistration\Ecosys\Services;

use App\Models\Meter\Meter;
use MPM\CustomBulkRegistration\Abstract\CreatorService;

class MeterService extends CreatorService
{
    public function __construct(Meter $meter)
    {
        parent::__construct($meter);
    }

    public function resolveCsvDataFromComingRow($csvData)
    {
        $meterConfig = [
            'serial_number' => 'device',
            'manufacturer_id' => 'manufacturer_id',
        ];

        $meterData = [
            'serial_number' => $csvData[$meterConfig['serial_number']],
            'in_use' => 1,
            'manufacturer_id' => $csvData[$meterConfig['manufacturer_id']],
            'meter_type_id' => 1,
        ];
        return $this->createRelatedDataIfDoesNotExists($meterData);

    }
}
