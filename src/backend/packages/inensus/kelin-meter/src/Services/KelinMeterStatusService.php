<?php

namespace Inensus\KelinMeter\Services;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Inensus\KelinMeter\Exceptions\KelinApiResponseException;
use Inensus\KelinMeter\Http\Clients\KelinMeterApiClient;

class KelinMeterStatusService {
    private $rootUrl = '/getMeterStatus';
    private $kelinApiClient;

    public function __construct(KelinMeterApiClient $kelinApiClient) {
        $this->kelinApiClient = $kelinApiClient;
    }

    public function getStatusOfMeter($meter) {
        try {
            $queryParams = [
                'meterType' => 1,
                'meterAddr' => $meter->meter_address,
            ];
            $result = $this->kelinApiClient->get($this->rootUrl, $queryParams);
            $result['data']['meterAddress'] = $meter->meter_address;
            $result['data']['owner'] = $meter->kelinCustomer->mpmPerson->name.' '.$meter->kelinCustomer->mpmPerson->surname;

            return (object) collect($result['data'])->all();
        } catch (KelinApiResponseException $exception) {
            throw new KelinApiResponseException($exception->getMessage());
        } catch (GuzzleException $exception) {
            Log::critical(
                'Unknown exception while authenticating KelinMeter',
                ['reason' => $exception->getMessage()]
            );
            throw new KelinApiResponseException($exception->getMessage());
        }
    }

    public function changeStatusOfMeter($meterAddress, $status) {
        try {
            $rootUrl = '/meterRemoteControl';
            $queryParams = [
                'meterType' => 1,
                'meterAddr' => $meterAddress,
                'cmd' => $status,
            ];

            return $this->kelinApiClient->get($rootUrl, $queryParams);
        } catch (KelinApiResponseException $exception) {
            throw new KelinApiResponseException($exception->getMessage());
        } catch (GuzzleException $exception) {
            Log::critical(
                'Unknown exception while authenticating KelinMeter',
                ['reason' => $exception->getMessage()]
            );
            throw new KelinApiResponseException($exception->getMessage());
        }
    }
}
