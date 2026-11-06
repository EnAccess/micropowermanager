<?php

namespace App\Plugins\CalinMeter\Services;

use App\Models\Manufacturer;

class ManufacturerService {
    public function __construct(
        private Manufacturer $manufacturer,
    ) {}

    public function register(): void {
        $api = $this->manufacturer->newQuery()->where('api_name', 'CalinMeterApi')->first();
        if (!$api) {
            $this->manufacturer->newQuery()->create([
                'name' => 'Calin Meter (Legacy Token API)',
                'type' => 'meter',
                'website' => 'http://www.calinmeter.com/',
                'api_name' => 'CalinMeterApi',
            ]);
        }
    }
}
