<?php

namespace Inensus\SteamaMeter\Helpers;

use App\Models\Manufacturer;
use Inensus\SteamaMeter\Exceptions\SteamaApiResponseException;

class ApiHelpers {
    private $manufacturer;

    public function __construct(Manufacturer $manufacturerModel) {
        $this->manufacturer = $manufacturerModel;
    }

    public function registerSparkMeterManufacturer() {
        $api = $this->manufacturer->newQuery()->where('api_name', 'SteamaMeterApi')->first();
        if (!$api) {
            $this->manufacturer->newQuery()->create([
                'name' => 'Steama Meters',
                'website' => 'https://steama.co/',
                'api_name' => 'SteamaMeterApi',
            ]);
        }
    }

    public function checkApiResult($result) {
        if (array_key_exists('detail', $result)) {
            throw new SteamaApiResponseException($result['detail']);
        }
        if (array_key_exists('non_field_errors', $result)) {
            throw new SteamaApiResponseException($result['non_field_errors']);
        }

        return $result;
    }

    public function makeHash($data) {
        return md5(implode('', $data));
    }
}
