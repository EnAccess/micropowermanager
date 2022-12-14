<?php

namespace Inensus\SunKingMeter\Services;

use App\Models\Manufacturer;


class ManufacturerService
{

    public function __construct(private Manufacturer $manufacturer)
    {
    }

    public function register()
    {
        $api = $this->manufacturer->newQuery()->where('api_name', 'SunKingMeterApi')->first();
        if (!$api) {
            $this->manufacturer->newQuery()->create([
                'name' => 'SunKing Meters',
                'website' => 'https://sunking.com/',
                'api_name' => 'SunKingMeterApi'
            ]);
        }
    }
}