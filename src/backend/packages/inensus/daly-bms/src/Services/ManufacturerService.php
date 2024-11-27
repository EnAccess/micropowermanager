<?php

namespace Inensus\DalyBms\Services;

use App\Models\Manufacturer;

class ManufacturerService {
    public function __construct(private Manufacturer $manufacturer) {}

    public function register() {
        $api = $this->manufacturer->newQuery()->where('api_name', 'DalyBmsApi')->first();
        if (!$api) {
            $this->manufacturer->newQuery()->create([
                'name' => 'DalyBms',
                'type' => 'e-bike',
                'website' => 'https://www.databms.com/',
                'api_name' => 'DalyBmsApi',
            ]);
        }
    }
}
