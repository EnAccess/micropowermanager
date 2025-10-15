<?php

namespace Inensus\StronMeter\Helpers;

use App\Models\Manufacturer;
use Inensus\StronMeter\Exceptions\StronApiResponseException;

class ApiHelpers {
    public function __construct(private Manufacturer $manufacturer) {}

    public function registerStronMeterManufacturer(): void {
        $api = $this->manufacturer->newQuery()->where('api_name', 'StronMeterApi')->first();
        if (!$api) {
            $this->manufacturer->newQuery()->create([
                'name' => 'Stron Meters',
                'website' => 'http://www.stronsmart.com/',
                'api_name' => 'StronMeterApi',
            ]);
        }
    }

    /**
     * @param array<string, mixed>|string $result
     *
     * @return array<string, mixed>|string
     */
    public function checkApiResult(array|string $result): array|string {
        if (is_array($result) && array_key_exists('Message', $result)) {
            throw new StronApiResponseException($result['Message']);
        }
        if ($result === 'false') {
            throw new StronApiResponseException('Returned false.');
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
