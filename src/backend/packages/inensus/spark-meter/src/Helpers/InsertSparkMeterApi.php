<?php

namespace Inensus\SparkMeter\Helpers;

use App\Models\Manufacturer;

class InsertSparkMeterApi {
    public function __construct(private Manufacturer $manufacturer) {}

    public function registerSparkMeterManufacturer(): void {
        $api = $this->manufacturer->newQuery()->where('api_name', 'SparkMeterApi')->first();
        if (!$api) {
            $this->manufacturer->newQuery()->create([
                'name' => 'Spark Meters',
                'website' => 'https://www.sparkmeter.io/',
                'api_name' => 'SparkMeterApi',
            ]);
        }
    }
}
