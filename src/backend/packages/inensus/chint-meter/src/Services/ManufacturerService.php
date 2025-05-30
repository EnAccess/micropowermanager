<?php

namespace Inensus\ChintMeter\Services;

use App\Models\Manufacturer;

class ManufacturerService {
    public function __construct(private Manufacturer $manufacturer) {}

    public function register() {
        $api = $this->manufacturer->newQuery()->where('api_name', 'ChintMeterApi')->first();
        if (!$api) {
            $this->manufacturer->newQuery()->create([
                'name' => 'Chint Meters',
                'website' => 'https://www.chintmeter.com/',
                'api_name' => 'ChintMeterApi',
            ]);
        }
    }
}
