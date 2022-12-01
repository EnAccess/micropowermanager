<?php

namespace Inensus\MicroStarMeter\Modules\Api;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Inensus\MicroStarMeter\Exceptions\MicroStarApiResponseException;
use Inensus\MicroStarMeter\Helpers\ResponseResolver;
use Inensus\MicroStarMeter\Models\MicroStarCredential;
use Inensus\MicroStarMeter\Services\MicroStarCredentialService;

class ApiRequests
{
    public function __construct(
        private Client $httpClient,
        private ResponseResolver $responseResolver
    ) {
    }

    public function get(MicroStarCredential $credentials, array $params, string $slug)
    {
         $url = $credentials->getApiUrl() . $slug;
         foreach ($params as $key => $value) {
             $url .= $key . '=' . $value . '&';
         }
        try {
            $request = $this->httpClient->get($url,
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer ' . $credentials->getApiKey(), // this part can be changed
                        // according to the API documentation
                    ],
                ]);

            return $this->responseResolver->checkResponse(json_decode((string)$request->getBody(), true));
        } catch (GuzzleException|MicroStarApiResponseException $exception) {
            Log::critical('MicroStar API Transaction Failed', [
                'message :' => $exception->getMessage()
            ]);
            throw new MicroStarApiResponseException($exception->getMessage());
        }
    }
}