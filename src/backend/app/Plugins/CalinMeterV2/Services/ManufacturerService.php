<?php

namespace App\Plugins\CalinMeterV2\Services;

use App\Models\Manufacturer;

class ManufacturerService {
    public function __construct(
        private Manufacturer $manufacturer,
    ) {}

    public function register(): void {
        $this->manufacturer->newQuery()->firstOrCreate(['api_name' => 'CalinMeterV2Api'], [
            'name' => 'Calin Meter API V2',
            'type' => 'meter',
            'website' => 'http://www.calinmeter.com/',
        ]);
    }
}
