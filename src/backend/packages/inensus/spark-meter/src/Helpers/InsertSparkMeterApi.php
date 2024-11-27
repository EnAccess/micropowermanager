<?php

namespace Inensus\SparkMeter\Helpers;

use App\Models\Manufacturer;

class InsertSparkMeterApi {
    private $manufacturer;

    public function __construct(
        Manufacturer $manufacturer,
    ) {
        $this->manufacturer = $manufacturer;
    }

    public function registerSparkMeterManufacturer() {
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
