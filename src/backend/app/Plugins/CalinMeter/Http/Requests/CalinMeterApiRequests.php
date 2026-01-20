<?php

namespace App\Plugins\CalinMeter\Http\Requests;

use App\Plugins\CalinMeter\Exceptions\CalinApiResponseException;
use App\Plugins\CalinMeter\Helpers\ApiHelpers;
use App\Plugins\CalinMeter\Models\CalinCredential;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class CalinMeterApiRequests {
    public function __construct(
        private Client $client,
        private ApiHelpers $apiHelpers,
        private CalinCredential $credential,
    ) {}

    /**
     * @param array<string, mixed> $postParams
     *
     * @return array<string, mixed>|string
     */
    public function post(string $url, array $postParams): array|string {
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

    public function getCredentials(): CalinCredential {
        return $this->credential->newQuery()->firstOrFail();
    }
}
