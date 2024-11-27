<?php

namespace Inensus\CalinMeter\Http\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Inensus\CalinMeter\Exceptions\CalinApiResponseException;
use Inensus\CalinMeter\Helpers\ApiHelpers;
use Inensus\CalinMeter\Models\CalinCredential;

class CalinMeterApiRequests {
    private $client;
    private $apiHelpers;
    private $credential;

    public function __construct(
        Client $httpClient,
        ApiHelpers $apiHelpers,
        CalinCredential $credentialModel,
    ) {
        $this->client = $httpClient;
        $this->apiHelpers = $apiHelpers;
        $this->credential = $credentialModel;
    }

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
                'Calin API Transaction Failed',
                [
                    'URL :' => $url,
                    'Body :' => json_encode($postParams),
                    'message :' => $gException->getMessage(),
                ]
            );
            throw new CalinApiResponseException($gException->getMessage());
        }
    }

    public function getCredentials() {
        return $this->credential->newQuery()->firstOrFail();
    }
}
