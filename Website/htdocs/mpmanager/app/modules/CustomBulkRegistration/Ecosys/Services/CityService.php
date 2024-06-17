<?php

namespace MPM\CustomBulkRegistration\Ecosys\Services;

use App\Models\City;
use MPM\CustomBulkRegistration\Abstract\CreatorService;

class CityService extends CreatorService
{
    public function __construct(City $city)
    {
        parent::__construct($city);
    }

    public function resolveCsvDataFromComingRow($csvData)
    {
        $cityConfig = [
            'cluster_id' => 'cluster_id',
            'mini_grid_id' => 'mini_grid_id',
            'name' => 'city',
        ];
        $cityData = [
            'cluster_id' => $csvData[$cityConfig['cluster_id']],
            'country_id' => 0,
            'mini_grid_id' => $csvData[$cityConfig['mini_grid_id']],
            'name' => $csvData[$cityConfig['name']],
        ];

        return $this->createRelatedDataIfDoesNotExists($cityData);
    }
}
