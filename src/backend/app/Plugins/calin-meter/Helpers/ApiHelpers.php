<?php

namespace Inensus\CalinMeter\Helpers;

use App\Models\Manufacturer;
use Inensus\CalinMeter\Exceptions\CalinApiResponseException;

class ApiHelpers {
    public function __construct(private Manufacturer $manufacturer) {}

    public function registerCalinMeterManufacturer(): void {
        $api = $this->manufacturer->newQuery()->where('api_name', 'CalinMeterApi')->first();
        if (!$api) {
            $this->manufacturer->newQuery()->create([
                'name' => 'Calin Meters',
                'website' => 'http://www.calinmeter.com/',
                'api_name' => 'CalinMeterApi',
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
            throw new CalinApiResponseException($result['reason']);
        }

        return $result['result'];
    }

    public function generateCipherText(
        int $serialID,
        int $userID,
        int $meterID,
        string $tokenType,
        float $amount,
        int $timestamp,
        string $key,
    ): string {
        return md5(
            sprintf(
                '%s%s%s%s%s%s%s',
                $serialID,
                $userID,
                $meterID,
                $tokenType,
                $amount,
                $timestamp,
                $key
            )
        );
    }
}
