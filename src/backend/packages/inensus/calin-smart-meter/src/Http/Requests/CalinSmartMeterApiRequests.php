<?php

namespace Inensus\CalinSmartMeter\Http\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Inensus\CalinSmartMeter\Exceptions\CalinSmartApiResponseException;
use Inensus\CalinSmartMeter\Helpers\ApiHelpers;

class CalinSmartMeterApiRequests {
    public function __construct(
        private Client $client,
        private ApiHelpers $apiHelpers,
    ) {}

    public function post($url, $postParams) {
        try {
            $request = $this->client->post(
                $url,
                [
                    'body' => json_encode($postParams),
                    'headers' => [
                        'Content-Type' => 'application/json;charset=utf-8',
                        'Content-Length:'.strlen(json_encode($postParams)),
                    ],
                ]
            );

            return $this->apiHelpers->checkApiResult(json_decode((string) $request->getBody(), true));
        } catch (GuzzleException $gException) {
            Log::critical(
                'Calin Smart API Transaction Failed',
                [
                    'URL :' => $url,
                    'Body :' => json_encode($postParams),
                    'message :' => $gException->getMessage(),
                ]
            );
            throw new CalinSmartApiResponseException($gException->getMessage());
        }
    }
}
