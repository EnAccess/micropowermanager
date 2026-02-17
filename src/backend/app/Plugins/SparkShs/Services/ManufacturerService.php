<?php

namespace App\Plugins\SparkShs\Services;

use App\Models\Manufacturer;

class ManufacturerService {
    public function __construct(
        private Manufacturer $manufacturer,
    ) {}

    public function register(): void {
        $api = $this->manufacturer->newQuery()->where('api_name', 'SparkShsApi')->first();
        if (!$api) {
            $this->manufacturer->newQuery()->create([
                'name' => 'Spark SHS',
                'type' => 'shs',
                'website' => 'https://sparkenergy.io/',
                'api_name' => 'SparkShsApi',
            ]);
        }
    }
}
