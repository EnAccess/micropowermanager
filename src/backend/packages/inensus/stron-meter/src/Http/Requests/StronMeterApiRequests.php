<?php

namespace Inensus\StronMeter\Http\Requests;

use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Inensus\StronMeter\Helpers\ApiHelpers;
use Inensus\StronMeter\Models\StronCredential;

class StronMeterApiRequests {
    public function __construct(private Client $client, private ApiHelpers $apiHelpers, private StronCredential $credential) {}

    public function token(string $url, $postParams) {
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
