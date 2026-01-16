<?php

namespace App\Plugins\CalinSmartMeter\Helpers;

use App\Models\Manufacturer;
use App\Plugins\CalinSmartMeter\Exceptions\CalinSmartApiResponseException;

class ApiHelpers {
    public function __construct(private Manufacturer $manufacturer) {}

    public function registerCalinMeterManufacturer(): void {
        $api = $this->manufacturer->newQuery()->where('api_name', 'CalinSmartMeterApi')->first();
        if (!$api) {
            $this->manufacturer->newQuery()->create([
                'name' => 'Calin Smart Meters',
                'website' => 'https://ami.calinhost.com/',
                'api_name' => 'CalinSmartMeterApi',
            ]);
        }
    }

    /**
     * @param array<string, mixed>|string $result
     *
     * @return array<string, mixed>|string
     */
    public function checkApiResult(array|string $result): array|string {
        if ((int) $result['result_code'] !== 0) {
            throw new CalinSmartApiResponseException($result['reason']);
        }

        return $result['result'];
    }
}
