<?php

namespace Inensus\KelinMeter\Services;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Inensus\KelinMeter\Exceptions\KelinApiResponseException;
use Inensus\KelinMeter\Http\Clients\KelinMeterApiClient;
use Inensus\KelinMeter\Models\KelinMeter;

class KelinMeterStatusService {
    private string $rootUrl = '/getMeterStatus';

    public function __construct(private KelinMeterApiClient $kelinApiClient) {}

    public function getStatusOfMeter(KelinMeter $meter): \stdClass {
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

    /**
     * @return array<string, mixed>|string
     */
    public function changeStatusOfMeter(string $meterAddress, string|int|bool $status): array|string {
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
