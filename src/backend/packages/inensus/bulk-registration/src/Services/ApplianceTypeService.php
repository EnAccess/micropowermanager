<?php

namespace Inensus\BulkRegistration\Services;

use App\Models\AssetType;

class ApplianceTypeService extends CreatorService {
    public function __construct(AssetType $assetType) {
        parent::__construct($assetType);
    }

    public function createRelatedDataIfDoesNotExists($appliances) {
        foreach ($appliances as $appliance) {
            AssetType::query()->firstOrCreate($appliance, $appliance);
        }
    }

    public function resolveCsvDataFromComingRow($csvData) {
        $applianceTypeConfig = config('bulk-registration.csv_fields.appliance_type');

        $applianceTypes = config('bulk-registration.appliance_types');

        $columnApplianceTypes = $csvData[$applianceTypeConfig['name']];

        $appliances = collect($applianceTypes)->map(function ($type) use ($columnApplianceTypes) {
            $applianceIndex = strpos($columnApplianceTypes, $type);
            if ($applianceIndex >= 0) {
                return [
                    'name' => $type,
                    'price' => 0,
                ];
            }

            return true;
        });

        return $this->createRelatedDataIfDoesNotExists($appliances);
    }
}
