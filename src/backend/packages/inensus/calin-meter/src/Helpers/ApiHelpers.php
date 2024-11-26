<?php

namespace Inensus\CalinMeter\Helpers;

use App\Models\Manufacturer;
use Inensus\CalinMeter\Exceptions\CalinApiResponseException;

class ApiHelpers {
    private $manufacturer;

    public function __construct(Manufacturer $manufacturerModel) {
        $this->manufacturer = $manufacturerModel;
    }

    public function registerCalinMeterManufacturer() {
        $api = $this->manufacturer->newQuery()->where('api_name', 'CalinMeterApi')->first();
        if (!$api) {
            $this->manufacturer->newQuery()->create([
                'name' => 'Calin Meters',
                'website' => 'http://www.calinmeter.com/',
                'api_name' => 'CalinMeterApi',
            ]);
        }
    }

    public function checkApiResult($result) {
        if ((int) $result['result_code'] !== 0) {
            throw new CalinApiResponseException($result['reason']);
        }

        return $result['result'];
    }

    public function generateCipherText(
        $serialID,
        $userID,
        $meterID,
        string $tokenType,
        float $amount,
        int $timestamp,
        $key,
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
