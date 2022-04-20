<?php

namespace App\Services;

use App\Models\GeographicalInformation;
use App\Models\Meter\Meter;

class MeterGeographicalInformationService extends BaseService
{

    public function __construct(
        private GeographicalInformation $geographicalInformation,
        private Meter $meter
    ) {
        parent::__construct([$geographicalInformation,$meter]);

    }


    public function updateGeographicalInformation(array $meters): array
    {
        collect($meters)->each(function ($meter) {
            $points = [
                $meter['lat'],
                $meter['lng']
            ];
            if ($points) {
                $meter = $this->meter->newQuery()->where('id', $meter['id'])
                    ->first();
                if ($meter){
                    $geo = $meter->meterParameter->geo;
                    $geo->points = $points[0] . ',' . $points[1];
                    $geo->save();
                }

            }
        });
        return ['data' => true];
    }

}