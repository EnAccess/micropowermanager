<?php

namespace Inensus\SteamaMeter\Helpers;

use App\Models\Manufacturer;
use Inensus\SteamaMeter\Exceptions\SteamaApiResponseException;

class ApiHelpers {
    public function __construct(private Manufacturer $manufacturer) {}

    public function registerSparkMeterManufacturer(): void {
        $api = $this->manufacturer->newQuery()->where('api_name', 'SteamaMeterApi')->first();
        if (!$api) {
            $this->manufacturer->newQuery()->create([
                'name' => 'Steama Meters',
                'website' => 'https://steama.co/',
                'api_name' => 'SteamaMeterApi',
            ]);
        }
    }

    public function checkApiResult(array $result): array {
        throw_if(array_key_exists('detail', $result), new SteamaApiResponseException($result['detail']));
        throw_if(array_key_exists('non_field_errors', $result), new SteamaApiResponseException($result['non_field_errors']));

        return $result;
    }

    public function makeHash($data): string {
        return md5(implode('', $data));
    }
}
