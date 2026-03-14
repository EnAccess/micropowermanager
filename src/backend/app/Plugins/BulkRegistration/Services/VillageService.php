<?php

namespace App\Plugins\BulkRegistration\Services;

use App\Models\Village;
use App\Plugins\BulkRegistration\Exceptions\VillageNotFoundException;

class VillageService extends CreatorService {
    public function __construct(Village $village) {
        parent::__construct($village);
    }

    /**
     * @param array<string, mixed> $csvData
     */
    public function resolveCsvDataFromComingRow(array $csvData) {
        $villageConfig = config('bulk-registration.csv_fields.village');

        if (!$csvData[$villageConfig['name']]) {
            throw new VillageNotFoundException('Village Name is required');
        }
        $registeredVillage = Village::query()->where('name', $csvData[$villageConfig['name']])->first();

        if (!$registeredVillage) {
            $message = 'There is no registered Village for '.$csvData[$villageConfig['name']].
                '. Please add the Village first.';

            throw new VillageNotFoundException($message);
        }

        $villageData = [
            'cluster_id' => $csvData[$villageConfig['cluster_id']],
            'country_id' => 0,
            'mini_grid_id' => $csvData[$villageConfig['mini_grid_id']],
            'name' => $csvData[$villageConfig['name']],
        ];

        return $this->createRelatedDataIfDoesNotExists($villageData);
    }
}
