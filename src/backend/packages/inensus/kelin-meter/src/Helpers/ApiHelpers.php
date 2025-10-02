<?php

namespace Inensus\KelinMeter\Helpers;

use App\Models\Manufacturer;
use Inensus\KelinMeter\Exceptions\KelinApiAuthenticationException;
use Inensus\KelinMeter\Exceptions\KelinApiEmtyDataException;
use Inensus\KelinMeter\Exceptions\KelinApiResponseException;

class ApiHelpers {
    private Manufacturer $manufacturer;

    public function __construct(Manufacturer $manufacturerModel) {
        $this->manufacturer = $manufacturerModel;
    }

    public function registerMeterManufacturer(): void {
        $this->manufacturer->newQuery()->firstOrCreate(['api_name' => 'KelinMeterApi'], [
            'name' => 'Kelin Meters',
            'website' => '-',
            'api_name' => 'KelinMeterApi',
        ]);
    }

    public function checkApiResult($result) {
        throw_unless($result, new KelinApiEmtyDataException('Null result returned.'));
        throw_if($result['status'] == -1, new KelinApiResponseException($result['error']));
        throw_if($result['status'] == -2, new KelinApiAuthenticationException($result['error']));
        throw_if(empty($result['data']), new KelinApiEmtyDataException('Data field of response is empty.'));

        return $result;
    }

    public function makeHash($data): string {
        return md5(implode('', $data));
    }
}
