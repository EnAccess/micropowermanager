<?php

namespace Inensus\SunKingSHS\Services;

use App\Models\Manufacturer;

class ManufacturerService {
    public function __construct(private Manufacturer $manufacturer) {}

    public function register() {
        $api = $this->manufacturer->newQuery()->where('api_name', 'SunKingSHSApi')->first();
        if (!$api) {
            $this->manufacturer->newQuery()->create([
                'name' => 'SunKing SHS',
                'type' => 'shs',
                'website' => 'https://sunking.com/',
                'api_name' => 'SunKingSHSApi',
            ]);
        }
    }
}
