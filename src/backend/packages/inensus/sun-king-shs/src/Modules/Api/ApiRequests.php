<?php

namespace Inensus\SunKingSHS\Modules\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Inensus\SunKingSHS\Exceptions\SunKingApiResponseException;
use Inensus\SunKingSHS\Models\SunKingCredential;

class ApiRequests {
    public function __construct(
        private Client $httpClient,
    ) {}

    public function authentication($credential): array {
        try {
            $response =
                $this->httpClient->post($credential->getAuthUrl(), [
                    'form_params' => [
                        'grant_type' => 'client_credentials',
                        'client_id' => $credential->getClientId(),
                        'client_secret' => $credential->getClientSecret(),
                        'scope' => 'roles',
                    ],
                ]);

            $body = json_decode((string) $response->getBody(), true);
            $token = $body['access_token'];
            $expiresIn = time() + (int) $body['expires_in'];

            return [
                'access_token' => $token,
                'token_expires_in' => $expiresIn,
            ];
        } catch (GuzzleException $e) {
            Log::critical('SunKing Access Token API request failed', [
                'message :' => $e->getMessage(),
            ]);
            throw new SunKingApiResponseException($e->getMessage());
        }
    }

    public function get(SunKingCredential $credentials, array $params, string $slug) {
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
                        'Authorization' => 'Bearer '.$credentials->getApiKey(),
                    ],
                ]
            );

            return json_decode((string) $request->getBody(), true);
        } catch (GuzzleException $e) {
            Log::critical('SunKing API request failed', [
                'message :' => $e->getMessage(),
            ]);
            throw new SunKingApiResponseException($e->getMessage());
        }
    }

    public function post(SunKingCredential $credentials, array $params, string $slug) {
        $url = $credentials->getApiUrl().$slug;
        try {
            $request = $this->httpClient->post(
                $url,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$credentials->getAccessToken(),
                    ],
                    'body' => json_encode($params),
                ]
            );

            return json_decode((string) $request->getBody(), true);
        } catch (GuzzleException $e) {
            Log::critical(
                'SunKing API Transaction Failed',
                [
                    'URL :' => $url,
                    'Body :' => json_encode($params),
                    'message :' => $e->getMessage(),
                ]
            );
            throw new SunKingApiResponseException($e->getMessage());
        }
    }
}
