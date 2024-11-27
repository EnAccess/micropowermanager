<?php

namespace Inensus\BulkRegistration\Services;

use App\Models\Manufacturer;
use Inensus\BulkRegistration\Exceptions\ManufacturerNotSupportedException;

class ManufacturerService extends CreatorService {
    private $manufacturers = [
        'Calin Meter',
        'Calin Smart Meter',
        'Kelin Meter',
        'Stron Meter',
        'GomeLong Meter',
        'MicroStar Meter',
        'SunKing SHS',
    ];

    public function __construct(Manufacturer $manufacturer) {
        parent::__construct($manufacturer);
    }

    public function resolveCsvDataFromComingRow($csvData) {
        $manufacturerConfig = config('bulk-registration.csv_fields.manufacturer');

        if (!in_array($csvData[$manufacturerConfig['name']], $this->manufacturers)) {
            $message = 'Manufacturer '.$csvData[$manufacturerConfig['name']].
                ' is not supported. Supported manufacturers are '.implode(', ', $this->manufacturers);
            throw new ManufacturerNotSupportedException($message);
        }
        if (strlen(preg_replace('/\s+/', '', $csvData[$manufacturerConfig['name']])) > 0) {
            $manufacturerData = [
                'name' => $csvData[$manufacturerConfig['name']].'s',
                'api_name' => preg_replace('/\s+/', '', $csvData[$manufacturerConfig['name']]).'Api',
            ];

            return $this->createRelatedDataIfDoesNotExists($manufacturerData);
        }

        return false;
    }
}
