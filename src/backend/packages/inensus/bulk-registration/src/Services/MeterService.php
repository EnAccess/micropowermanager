<?php

namespace Inensus\BulkRegistration\Services;

use App\Models\Meter\Meter;

class MeterService extends CreatorService {
    private $meterTypeService;

    public function __construct(Meter $meter, MeterTypeService $meterTypeService) {
        parent::__construct($meter);
        $this->meterTypeService = $meterTypeService;
    }

    public function resolveCsvDataFromComingRow($csvData) {
        $meterConfig = config('bulk-registration.csv_fields.meter');

        if (strlen(preg_replace('/\s+/', '', $csvData[$meterConfig['serial_number']])) > 0) {
            $meterType = $this->meterTypeService->createDefaultMeterTypeIfDoesNotExistAny();
            $meterData = [
                'serial_number' => $csvData[$meterConfig['serial_number']],
                'in_use' => $meterConfig['in_use'],
                'manufacturer_id' => $csvData[$meterConfig['manufacturer_id']],
                'meter_type_id' => $meterType->id,
            ];

            return $this->createRelatedDataIfDoesNotExists($meterData);
        }

        return false;
    }
}
