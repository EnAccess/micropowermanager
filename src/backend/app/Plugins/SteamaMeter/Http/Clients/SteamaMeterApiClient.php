<?php

namespace App\Plugins\SteamaMeter\Http\Clients;

use App\Plugins\SteamaMeter\Exceptions\ModelNotFoundException;
use App\Plugins\SteamaMeter\Exceptions\SteamaApiResponseException;
use App\Plugins\SteamaMeter\Helpers\ApiHelpers;
use App\Plugins\SteamaMeter\Models\SteamaCredential;
use App\Traits\EncryptsCredentials;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class SteamaMeterApiClient {
    use EncryptsCredentials;

    public function __construct(
        private Client $client,
        private ApiHelpers $apiHelpers,
        private SteamaCredential $credential,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function get(string $url): array {
        try {
            $credential = $this->getCredentials();
        } catch (\Exception $e) {
            throw new ModelNotFoundException($e->getMessage(), $e->getCode(), $e);
        }
        try {
            $request = $this->client->get(
                $credential->api_url.$url,
                [
                    'timeout' => config('steama-meter.request_timeout'),
                    'headers' => [
                        'Content-Type' => 'application/json;charset=utf-8',
                        'Authorization' => 'Token '.$credential->authentication_token,
                    ],
                ]
            );
        } catch (GuzzleException $exception) {
            throw new SteamaApiResponseException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $this->apiHelpers->checkApiResult(json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * Fetches every page of a paginated Steama collection and returns the merged `results`.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllResults(string $rootUrl): array {
        $result = $this->get($rootUrl.'?page=1&page_size=100');
        $results = $result['results'];
        while ($result['next']) {
            $result = $this->get($rootUrl.'?'.explode('?', $result['next'])[1]);
            foreach ($result['results'] as $item) {
                $results[] = $item;
            }
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $postParams
     *
     * @return array<string, mixed>
     */
    public function token(string $url, array $postParams): array {
        try {
            $credential = $this->getCredentials();
        } catch (\Exception $e) {
            throw new ModelNotFoundException($e->getMessage(), $e->getCode(), $e);
        }
        try {
            $request = $this->client->post(
                $credential->api_url.$url,
                [
                    'timeout' => config('steama-meter.request_timeout'),
                    'body' => json_encode($postParams),
                    'headers' => [
                        'Content-Type' => 'application/json;charset=utf-8',
                    ],
                ]
            );
        } catch (GuzzleException $exception) {
            throw new SteamaApiResponseException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $this->apiHelpers->checkApiResult(json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<string, mixed> $postParams
     *
     * @return array<string, mixed>
     */
    public function post(string $url, array $postParams): array {
        try {
            $credential = $this->getCredentials();
        } catch (\Exception $e) {
            throw new ModelNotFoundException($e->getMessage(), $e->getCode(), $e);
        }
        try {
            $request = $this->client->post(
                $credential->api_url.$url,
                [
                    'timeout' => config('steama-meter.request_timeout'),
                    'body' => json_encode($postParams),
                    'headers' => [
                        'Content-Type' => 'application/json;charset=utf-8',
                        'Authorization' => 'Token '.$credential->authentication_token,
                    ],
                ]
            );
        } catch (GuzzleException $exception) {
            throw new SteamaApiResponseException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $this->apiHelpers->checkApiResult(json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<string, mixed> $putParams
     *
     * @return array<string, mixed>
     */
    public function put(string $url, array $putParams): array {
        try {
            $credential = $this->getCredentials();
        } catch (\Exception $e) {
            throw new ModelNotFoundException($e->getMessage(), $e->getCode(), $e);
        }
        try {
            $request = $this->client->put(
                $credential->api_url.$url,
                [
                    'timeout' => config('steama-meter.request_timeout'),
                    'body' => json_encode($putParams),
                    'headers' => [
                        'Content-Type' => 'application/json;charset=utf-8',
                        'Authorization' => 'Token '.$credential->authentication_token,
                    ],
                ]
            );
        } catch (GuzzleException $exception) {
            throw new SteamaApiResponseException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $this->apiHelpers->checkApiResult(json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<string, mixed> $putParams
     *
     * @return array<string, mixed>
     */
    public function patch(string $url, $putParams): array {
        try {
            $credential = $this->getCredentials();
        } catch (\Exception $e) {
            throw new ModelNotFoundException($e->getMessage(), $e->getCode(), $e);
        }
        try {
            $request = $this->client->patch(
                $credential->api_url.$url,
                [
                    'timeout' => config('steama-meter.request_timeout'),
                    'body' => json_encode($putParams),
                    'headers' => [
                        'Content-Type' => 'application/json;charset=utf-8',
                        'Authorization' => 'Token '.$credential->authentication_token,
                    ],
                ]
            );
        } catch (GuzzleException $exception) {
            throw new SteamaApiResponseException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $this->apiHelpers->checkApiResult(json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    public function getByParams(string $url, array $params): array {
        try {
            $credential = $this->getCredentials();
        } catch (\Exception $e) {
            throw new ModelNotFoundException($e->getMessage(), $e->getCode(), $e);
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
                    'timeout' => config('steama-meter.request_timeout'),
                    'headers' => [
                        'Content-Type' => 'application/json;charset=utf-8',
                        'Authorization' => 'Token '.$credential->authentication_token,
                    ],
                ]
            );
        } catch (GuzzleException $exception) {
            throw new SteamaApiResponseException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $this->apiHelpers->checkApiResult(json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function getCredentials(): SteamaCredential {
        $credential = $this->credential->newQuery()->firstOrFail();
        $credential->authentication_token = $this->decryptCredentialField($credential->authentication_token);

        return $credential;
    }
}
