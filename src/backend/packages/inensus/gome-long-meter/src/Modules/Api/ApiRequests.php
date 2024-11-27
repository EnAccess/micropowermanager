<?php

namespace Inensus\GomeLongMeter\Modules\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Inensus\GomeLongMeter\Exceptions\GomeLongApiResponseException;
use Inensus\GomeLongMeter\Models\GomeLongCredential;

class ApiRequests {
    public function __construct(
        private Client $httpClient,
    ) {}

    public function get(GomeLongCredential $credentials, array $params, string $slug) {
        $url = $credentials->getApiUrl().$slug;
        foreach ($params as $key => $value) {
            $url .= $key.'='.$value.'&';
        }
        try {
            $request = $this->httpClient->get(
                $url,
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ]
            );
            $result = json_decode((string) $request->getBody(), true);

            if ($result['ReturnCode'] !== 0) {
                return $result['Data'];
            } else {
                throw new GomeLongApiResponseException($result['ReturnMessage']);
            }
        } catch (GuzzleException|GomeLongApiResponseException $e) {
            Log::critical('GomeLong API request failed', [
                'message :' => $e->getMessage(),
            ]);
            throw new GomeLongApiResponseException($e->getMessage());
        }
    }

    public function post(GomeLongCredential $credentials, array $params, string $slug) {
        $url = $credentials->getApiUrl().$slug;
        try {
            $request = $this->httpClient->post(
                $url,
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-type' => 'application/x-www-form-urlencoded',
                    ],
                    'form_params' => $params,
                ]
            );
            $result = json_decode((string) $request->getBody(), true);

            if ($result['ReturnCode'] !== 0) {
                return $result['Data'];
            } else {
                throw new GomeLongApiResponseException($result['ReturnMessage']);
            }
        } catch (GuzzleException|GomeLongApiResponseException $e) {
            Log::critical('GomeLong API request failed', [
                'message :' => $e->getMessage(),
            ]);
            throw new GomeLongApiResponseException($e->getMessage());
        }
    }
}
