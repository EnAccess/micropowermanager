<?php

namespace Inensus\CalinSmartMeter\Helpers;

use App\Models\Manufacturer;
use Inensus\CalinSmartMeter\Exceptions\CalinSmartApiResponseException;

class ApiHelpers {
    private Manufacturer $manufacturer;

    public function __construct(Manufacturer $manufacturerModel) {
        $this->manufacturer = $manufacturerModel;
    }

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

    public function checkApiResult(array $result) {
        if ((int) $result['result_code'] !== 0) {
            throw new CalinSmartApiResponseException($result['reason']);
        }

        return $result['result'];
    }
}
