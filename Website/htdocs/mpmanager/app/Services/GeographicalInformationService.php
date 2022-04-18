<?php

namespace App\Services;

use App\Models\GeographicalInformation;

class GeographicalInformationService
{
    public function __construct(
        private SessionService $sessionService,
        private GeographicalInformation $geographicalInformation
    ) {
        $this->sessionService->setModel($geographicalInformation);
    }


    public function makeGeographicalInformation($geoPoints)
    {
        return $this->geographicalInformation->newQuery()->make([
            'points' => $geoPoints,
        ]);
    }
}