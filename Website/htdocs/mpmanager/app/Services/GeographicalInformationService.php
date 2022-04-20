<?php

namespace App\Services;

use App\Models\GeographicalInformation;

class GeographicalInformationService extends BaseService
{
    public function __construct(
        private GeographicalInformation $geographicalInformation
    ) {
        parent::__construct([$geographicalInformation]);
    }


    public function makeGeographicalInformation($geoPoints)
    {
        return $this->geographicalInformation->newQuery()->make([
            'points' => $geoPoints,
        ]);
    }
}