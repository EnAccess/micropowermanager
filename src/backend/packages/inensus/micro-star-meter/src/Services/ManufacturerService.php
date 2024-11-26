<?php

namespace Inensus\MicroStarMeter\Services;

use App\Models\Manufacturer;

class ManufacturerService {
    public function __construct(private Manufacturer $manufacturer) {}

    public function register() {
        $api = $this->manufacturer->newQuery()->where('api_name', 'MicroStarMeterApi')->first();
        if (!$api) {
            $this->manufacturer->newQuery()->create([
                'name' => 'MicroStar Meters',
                'website' => 'https://www.microstarelectric.com/',
                'api_name' => 'MicroStarMeterApi',
            ]);
        }
    }
}
