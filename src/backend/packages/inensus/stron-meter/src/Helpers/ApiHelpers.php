<?php

namespace Inensus\StronMeter\Helpers;

use App\Models\Manufacturer;
use Inensus\StronMeter\Exceptions\StronApiResponseException;

class ApiHelpers {
    private $manufacturer;

    public function __construct(Manufacturer $manufacturerModel) {
        $this->manufacturer = $manufacturerModel;
    }

    public function registerStronMeterManufacturer() {
        $api = $this->manufacturer->newQuery()->where('api_name', 'StronMeterApi')->first();
        if (!$api) {
            $this->manufacturer->newQuery()->create([
                'name' => 'Stron Meters',
                'website' => 'http://www.stronsmart.com/',
                'api_name' => 'StronMeterApi',
            ]);
        }
    }

    public function checkApiResult($result) {
        if (is_array($result) && array_key_exists('Message', $result)) {
            throw new StronApiResponseException($result['Message']);
        }
        if ($result === 'false') {
            throw new StronApiResponseException('Returned false.');
        }

        return $result;
    }

    public function makeHash($data) {
        return md5(implode('', $data));
    }
}
