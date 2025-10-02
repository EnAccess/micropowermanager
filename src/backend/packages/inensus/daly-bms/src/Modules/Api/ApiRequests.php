<?php

namespace Inensus\DalyBms\Modules\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Inensus\DalyBms\Exceptions\DalyBmsApiResponseException;
use Inensus\DalyBms\Models\DalyBmsCredential;

class ApiRequests {
    public function __construct(
        private Client $httpClient,
    ) {}

    public function authentication($credential): array {
        try {
            $slug = '/Login/Authenticate?';
            $userName = $credential->getUserName();
            $password = $credential->getPassword();
            $response =
                $this->httpClient->post($credential->getApiUrl().$slug.'Username='.$userName.'&Password='.$password);

            $body = json_decode((string) $response->getBody(), true);
            $status = $body['status'];

            if ($status !== 200) {
                throw new DalyBmsApiResponseException($body['message']);
            }

            $response = $body['response'];
            $token = $response['token'];
            $expiresIn = time() + (int) $response['expires_in'];

            return [
                'access_token' => $token,
                'token_expires_in' => $expiresIn,
            ];
        } catch (GuzzleException $e) {
            Log::critical('Daly Bms Access Token API request failed', [
                'message :' => $e->getMessage(),
            ]);
            throw new DalyBmsApiResponseException($e->getMessage());
        }
    }

    public function postWithBodyParams(DalyBmsCredential $credentials, array $params, string $slug) {
        $url = $credentials->getApiUrl().$slug;
        try {
            $response = $this->httpClient->post(
                $url,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$credentials->getAccessToken(),
                    ],
                    'body' => json_encode($params),
                ]
            );
            $body = json_decode((string) $response->getBody(), true);
            $status = $body['status'];

            if (($status !== 200 && $status !== 201) || (!is_array($body['response']) && $body['response'] === false)) {
                throw new DalyBmsApiResponseException($body['msg']);
            }

            return $body['response']['Data'];
        } catch (GuzzleException $e) {
            Log::critical(
                'Daly Bms API call failed',
                [
                    'URL :' => $url,
                    'Body :' => json_encode($params),
                    'message :' => $e->getMessage(),
                ]
            );
            throw $e;
        }
    }

    public function postWithQueryParams(DalyBmsCredential $credentials, array $params, string $slug) {
        $url = $credentials->getApiUrl().$slug;

        if (strpos($url, '?') === false) {
            $url .= '?'.http_build_query($params);
        } else {
            $url .= '&'.http_build_query($params);
        }

        try {
            $response = $this->httpClient->post(
                $url,
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$credentials->getAccessToken(),
                    ],
                ]
            );
            $body = json_decode((string) $response->getBody(), true);
            $status = $body['status'];
            if ($status !== 200 && $status !== 201) {
                throw new DalyBmsApiResponseException($body['message']);
            }
            $response = $body['response'];

            return $response['data'];
        } catch (GuzzleException $e) {
            Log::critical(
                'Daly Bms API call failed',
                [
                    'URL :' => $url,
                    'Body :' => json_encode($params),
                    'message :' => $e->getMessage(),
                ]
            );
            throw $e;
        }
    }
}
