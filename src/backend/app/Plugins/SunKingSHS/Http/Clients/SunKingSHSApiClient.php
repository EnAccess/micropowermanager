<?php

namespace App\Plugins\SunKingSHS\Http\Clients;

use App\Plugins\SunKingSHS\Exceptions\SunKingApiResponseException;
use App\Plugins\SunKingSHS\Models\SunKingCredential;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class SunKingSHSApiClient {
    public function __construct(
        private Client $httpClient,
    ) {}

    /**
     * @return array{access_token: string, token_expires_in: int}
     */
    public function authentication(SunKingCredential $credential): array {
        try {
            $response =
                $this->httpClient->post($credential->auth_url, [
                    'form_params' => [
                        'grant_type' => 'client_credentials',
                        'client_id' => $credential->client_id,
                        'client_secret' => $credential->client_secret,
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
            throw new SunKingApiResponseException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    public function get(SunKingCredential $credentials, array $params, string $slug): mixed {
        $url = $credentials->api_url.$slug;
        foreach ($params as $key => $value) {
            $url .= $key.'='.$value.'&';
        }
        try {
            $request = $this->httpClient->get(
                $url,
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer '.$credentials->access_token,
                    ],
                ]
            );

            return json_decode((string) $request->getBody(), true);
        } catch (GuzzleException $e) {
            Log::critical('SunKing API request failed', [
                'message :' => $e->getMessage(),
            ]);
            throw new SunKingApiResponseException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>|string
     */
    public function post(SunKingCredential $credentials, array $params, string $slug): array|string {
        $url = $credentials->api_url.$slug;
        try {
            $request = $this->httpClient->post(
                $url,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$credentials->access_token,
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
            throw new SunKingApiResponseException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
