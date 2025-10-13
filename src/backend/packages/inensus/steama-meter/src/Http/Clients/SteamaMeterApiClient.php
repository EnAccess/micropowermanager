<?php

namespace Inensus\SteamaMeter\Http\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Inensus\SteamaMeter\Exceptions\ModelNotFoundException;
use Inensus\SteamaMeter\Exceptions\SteamaApiResponseException;
use Inensus\SteamaMeter\Helpers\ApiHelpers;
use Inensus\SteamaMeter\Models\SteamaCredential;

class SteamaMeterApiClient {
    public function __construct(private Client $client, private ApiHelpers $apiHelpers, private SteamaCredential $credential) {}

    public function get(string $url): array {
        try {
            $credential = $this->getCredentials();
        } catch (\Exception $e) {
            throw new ModelNotFoundException($e->getMessage());
        }
        try {
            $request = $this->client->get(
                $credential->api_url.$url,
                [
                    'headers' => [
                        'Content-Type' => 'application/json;charset=utf-8',
                        'Authorization' => 'Token '.$credential->authentication_token,
                    ],
                ]
            );
        } catch (GuzzleException $exception) {
            throw new SteamaApiResponseException($exception->getMessage());
        }

        return $this->apiHelpers->checkApiResult(json_decode((string) $request->getBody(), true));
    }

    public function token(string $url, $postParams): array {
        try {
            $credential = $this->getCredentials();
        } catch (\Exception $e) {
            throw new ModelNotFoundException($e->getMessage());
        }
        try {
            $request = $this->client->post(
                $credential->api_url.$url,
                [
                    'body' => json_encode($postParams),
                    'headers' => [
                        'Content-Type' => 'application/json;charset=utf-8',
                    ],
                ]
            );
        } catch (GuzzleException $exception) {
            throw new SteamaApiResponseException($exception->getMessage());
        }

        return $this->apiHelpers->checkApiResult(json_decode((string) $request->getBody(), true));
    }

    public function post(string $url, $postParams): array {
        try {
            $credential = $this->getCredentials();
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException($e->getMessage());
        }
        try {
            $request = $this->client->post(
                $credential->api_url.$url,
                [
                    'body' => json_encode($postParams),
                    'headers' => [
                        'Content-Type' => 'application/json;charset=utf-8',
                        'Authorization' => 'Token '.$credential->authentication_token,
                    ],
                ]
            );
        } catch (GuzzleException $exception) {
            throw new SteamaApiResponseException($exception->getMessage());
        }

        return $this->apiHelpers->checkApiResult(json_decode((string) $request->getBody(), true));
    }

    public function put(string $url, $putParams): array {
        try {
            $credential = $this->getCredentials();
        } catch (\Exception $e) {
            throw new ModelNotFoundException($e->getMessage());
        }

        try {
            $request = $this->client->put(
                $credential->api_url.$url,
                [
                    'body' => json_encode($putParams),
                    'headers' => [
                        'Content-Type' => 'application/json;charset=utf-8',
                        'Authorization' => 'Token '.$credential->authentication_token,
                    ],
                ]
            );
        } catch (GuzzleException $exception) {
            throw new SteamaApiResponseException($exception->getMessage());
        }

        return $this->apiHelpers->checkApiResult(json_decode((string) $request->getBody(), true));
    }

    public function patch(string $url, $putParams): array {
        try {
            $credential = $this->getCredentials();
        } catch (\Exception $e) {
            throw new ModelNotFoundException($e->getMessage());
        }
        try {
            $request = $this->client->patch(
                $credential->api_url.$url,
                [
                    'body' => json_encode($putParams),
                    'headers' => [
                        'Content-Type' => 'application/json;charset=utf-8',
                        'Authorization' => 'Token '.$credential->authentication_token,
                    ],
                ]
            );
        } catch (GuzzleException $exception) {
            throw new SteamaApiResponseException($exception->getMessage());
        }

        return $this->apiHelpers->checkApiResult(json_decode((string) $request->getBody(), true));
    }

    public function getByParams(string $url, $params): mixed {
        try {
            $credential = $this->getCredentials();
        } catch (\Exception $e) {
            throw new ModelNotFoundException($e->getMessage());
        }
        $apiUrl = $credential->api_url.$url.'?';
        foreach ($params as $key => $value) {
            $apiUrl .= $key.'='.$value.'&';
        }
        $apiUrl = substr($apiUrl, 0, -1);

        try {
            $request = $this->client->get(
                $apiUrl,
                [
                    'headers' => [
                        'Content-Type' => 'application/json;charset=utf-8',
                        'Authorization' => 'Token '.$credential->authentication_token,
                    ],
                ]
            );
        } catch (GuzzleException $exception) {
            throw new SteamaApiResponseException($exception->getMessage());
        }

        return json_decode((string) $request->getBody(), true);
    }

    public function getCredentials() {
        return $this->credential->newQuery()->firstOrFail();
    }
}
