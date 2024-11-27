<?php

namespace Inensus\BulkRegistration\Services;

use App\Models\City;
use Inensus\BulkRegistration\Exceptions\VillageNotFoundException;

class CityService extends CreatorService {
    public function __construct(City $city) {
        parent::__construct($city);
    }

    public function resolveCsvDataFromComingRow($csvData) {
        $cityConfig = config('bulk-registration.csv_fields.city');

        if (!$csvData[$cityConfig['name']]) {
            throw new VillageNotFoundException('Village Name is required');
        }
        $registeredCity = City::query()->where('name', $csvData[$cityConfig['name']])->first();

        if (!$registeredCity) {
            $message = 'There is no registered Village for '.$csvData[$cityConfig['name']].
                '. Please add the Village first.';

            throw new VillageNotFoundException($message);
        }

        $cityData = [
            'cluster_id' => $csvData[$cityConfig['cluster_id']],
            'country_id' => 0,
            'mini_grid_id' => $csvData[$cityConfig['mini_grid_id']],
            'name' => $csvData[$cityConfig['name']],
        ];

        return $this->createRelatedDataIfDoesNotExists($cityData);
    }
}
