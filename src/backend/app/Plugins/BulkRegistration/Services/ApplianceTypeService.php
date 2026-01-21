<?php

namespace App\Plugins\BulkRegistration\Services;

use App\Models\ApplianceType;

class ApplianceTypeService extends CreatorService {
    public function __construct(ApplianceType $applianceType) {
        parent::__construct($applianceType);
    }

    public function createRelatedDataIfDoesNotExists($appliances): void {
        foreach ($appliances as $appliance) {
            ApplianceType::query()->firstOrCreate($appliance, $appliance);
        }
    }

    /**
     * @param array<string, mixed> $csvData
     */
    public function resolveCsvDataFromComingRow(array $csvData): void {
        $applianceTypeConfig = config('bulk-registration.csv_fields.appliance_type');

        /**
         * @var array<int, string>
         */
        $applianceTypes = config('bulk-registration.appliance_types');

        $columnApplianceTypes = $csvData[$applianceTypeConfig['name']];

        $appliances = collect($applianceTypes)->map(function ($type) use ($columnApplianceTypes): array|true {
            $applianceIndex = strpos($columnApplianceTypes, $type);
            if ($applianceIndex !== false) {
                return [
                    'name' => $type,
                    'price' => 0,
                ];
            }

            return true;
        });

        $this->createRelatedDataIfDoesNotExists($appliances->all());
    }
}
