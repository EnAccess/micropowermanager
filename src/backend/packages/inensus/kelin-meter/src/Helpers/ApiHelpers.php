<?php

namespace Inensus\KelinMeter\Helpers;

use App\Models\Manufacturer;
use Inensus\KelinMeter\Exceptions\KelinApiAuthenticationException;
use Inensus\KelinMeter\Exceptions\KelinApiEmtyDataException;
use Inensus\KelinMeter\Exceptions\KelinApiResponseException;

class ApiHelpers {
    private $manufacturer;

    public function __construct(Manufacturer $manufacturerModel) {
        $this->manufacturer = $manufacturerModel;
    }

    public function registerMeterManufacturer() {
        $this->manufacturer->newQuery()->firstOrCreate(['api_name' => 'KelinMeterApi'], [
            'name' => 'Kelin Meters',
            'website' => '-',
            'api_name' => 'KelinMeterApi',
        ]);
    }

    public function checkApiResult($result) {
        if (!$result) {
            throw new KelinApiEmtyDataException('Null result returned.');
        }
        if ($result['status'] == -1) {
            throw new KelinApiResponseException($result['error']);
        }
        if ($result['status'] == -2) {
            throw new KelinApiAuthenticationException($result['error']);
        }
        if (empty($result['data'])) {
            throw new KelinApiEmtyDataException('Data field of response is empty.');
        }

        return $result;
    }

    public function makeHash($data) {
        return md5(implode('', $data));
    }
}
