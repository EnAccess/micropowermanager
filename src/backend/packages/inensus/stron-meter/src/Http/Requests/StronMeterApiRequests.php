<?php

namespace Inensus\StronMeter\Http\Requests;

use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Inensus\StronMeter\Helpers\ApiHelpers;
use Inensus\StronMeter\Models\StronCredential;

class StronMeterApiRequests {
    private $client;
    private $apiHelpers;
    private $credential;

    public function __construct(
        Client $httpClient,
        ApiHelpers $apiHelpers,
        StronCredential $credentialModel,
    ) {
        $this->client = $httpClient;
        $this->apiHelpers = $apiHelpers;
        $this->credential = $credentialModel;
    }

    public function token($url, $postParams) {
        try {
            $credential = $this->getCredentials();
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException($e->getMessage());
        }
        $request = $this->client->post(
            $credential->api_url.$url,
            [
                'body' => json_encode($postParams),
                'headers' => [
                    'Content-Type' => 'application/json;charset=utf-8',
                ],
            ]
        );

        return $this->apiHelpers->checkApiResult(json_decode((string) $request->getBody(), true));
    }

    public function getCredentials() {
        return $this->credential->newQuery()->firstOrFail();
    }
}
