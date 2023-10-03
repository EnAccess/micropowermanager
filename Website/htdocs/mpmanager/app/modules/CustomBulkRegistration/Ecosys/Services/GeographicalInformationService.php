<?php

namespace MPM\CustomBulkRegistration\Ecosys\Services;

use App\Models\GeographicalInformation;
use MPM\CustomBulkRegistration\Abstract\CreatorService;

class GeographicalInformationService extends CreatorService
{

    public function __construct(GeographicalInformation $geo)
    {
        parent::__construct($geo);
    }

    public function resolveCsvDataFromComingRow($csvData)
    {
        $geoConfig =  [
            'owner_type' => 'meter_parameter',
            'owner_id' => 'meter_parameter_id',
            'points' => 'location',
        ];
        $geographicalInformationData = [
            'points' => $csvData[$geoConfig['points']],
            'owner_type' => $geoConfig['owner_type'],
            'owner_id' => $csvData[$geoConfig['owner_id']],
            ];

        return $this->createRelatedDataIfDoesNotExists($geographicalInformationData);
    }
}