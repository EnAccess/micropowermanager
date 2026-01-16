<?php

namespace App\Plugins\GomeLongMeter\Services;

use App\Models\Manufacturer;

class ManufacturerService {
    public function __construct(private Manufacturer $manufacturer) {}

    public function register(): void {
        $api = $this->manufacturer->newQuery()->where('api_name', 'GomeLongMeterApi')->first();
        if (!$api) {
            $this->manufacturer->newQuery()->create([
                'name' => 'GomeLong Meters',
                'website' => 'https://www.gomelongmeter.com/',
                'api_name' => 'GomeLongMeterApi',
            ]);
        }
    }
}
