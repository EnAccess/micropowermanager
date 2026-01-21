<?php

namespace App\Plugins\AngazaSHS\Services;

use App\Models\Manufacturer;

class ManufacturerService {
    public function __construct(private Manufacturer $manufacturer) {}

    public function register(): void {
        $api = $this->manufacturer->newQuery()->where('api_name', 'AngazaSHSApi')->first();
        if (!$api) {
            $this->manufacturer->newQuery()->create([
                'name' => 'Angaza SHS',
                'type' => 'shs',
                'website' => 'https://devices.angaza.com/',
                'api_name' => 'AngazaSHSApi',
            ]);
        }
    }
}
