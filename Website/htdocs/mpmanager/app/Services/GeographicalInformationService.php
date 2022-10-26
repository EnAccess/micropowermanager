<?php

namespace App\Services;

use App\Models\GeographicalInformation;

class GeographicalInformationService implements IBaseService
{
    public function __construct(
        private GeographicalInformation $geographicalInformation
    ) {
    }


    public function makeGeographicalInformation($geoPoints): GeographicalInformation
    {
        /** @var GeographicalInformation $model */
        $model =  $this->geographicalInformation->newQuery()->make([
            'points' => $geoPoints,
        ]);

        return $model;
    }
}
