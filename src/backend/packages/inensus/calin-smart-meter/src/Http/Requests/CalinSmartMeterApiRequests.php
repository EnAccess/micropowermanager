<?php

namespace Inensus\CalinSmartMeter\Http\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Inensus\CalinSmartMeter\Exceptions\CalinSmartApiResponseException;
use Inensus\CalinSmartMeter\Helpers\ApiHelpers;
use Inensus\CalinSmartMeter\Models\CalinSmartCredential;

class CalinSmartMeterApiRequests {
    private $client;
    private $apiHelpers;
    private $credential;

    public function __construct(
        Client $httpClient,
        ApiHelpers $apiHelpers,
        CalinSmartCredential $credentialModel,
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
