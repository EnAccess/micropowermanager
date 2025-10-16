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
        if (array_key_exists('detail', $result)) {
            throw new SteamaApiResponseException($result['detail']);
        }
        if (array_key_exists('non_field_errors', $result)) {
            throw new SteamaApiResponseException($result['non_field_errors']);
        }

        return $result;
    }

    /**
     * @param array<string|int, mixed> $data
     */
    public function makeHash(array $data): string {
        return md5(implode('', $data));
    }
}
