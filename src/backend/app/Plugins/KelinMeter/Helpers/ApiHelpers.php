<?php

namespace App\Plugins\KelinMeter\Helpers;

use App\Models\Manufacturer;
use App\Plugins\KelinMeter\Exceptions\KelinApiAuthenticationException;
use App\Plugins\KelinMeter\Exceptions\KelinApiEmtyDataException;
use App\Plugins\KelinMeter\Exceptions\KelinApiResponseException;

class ApiHelpers {
    public function __construct(private Manufacturer $manufacturer) {}

    public function registerMeterManufacturer(): void {
        $this->manufacturer->newQuery()->firstOrCreate(['api_name' => 'KelinMeterApi'], [
            'name' => 'Kelin Meters',
            'website' => '-',
            'api_name' => 'KelinMeterApi',
        ]);
    }

    /**
     * @param array<string, mixed>|string $result
     *
     * @return array<string, mixed>|string
     */
    public function checkApiResult(array|string $result): array|string {
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

    /**
     * @param array<string|int, mixed> $data
     */
    public function makeHash(array $data): string {
        return md5(implode('', $data));
    }
}
