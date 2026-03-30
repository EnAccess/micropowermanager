<?php

namespace App\Plugins\CalinSmartMeter\Http\Requests;

use App\Plugins\CalinSmartMeter\Exceptions\CalinSmartApiResponseException;
use App\Plugins\CalinSmartMeter\Helpers\ApiHelpers;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class CalinSmartMeterApiRequests {
    public function __construct(
        private Client $client,
        private ApiHelpers $apiHelpers,
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
