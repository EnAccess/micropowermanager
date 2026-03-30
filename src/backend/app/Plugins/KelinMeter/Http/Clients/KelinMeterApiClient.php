<?php

namespace App\Plugins\KelinMeter\Http\Clients;

use App\Plugins\KelinMeter\Exceptions\KelinApiCredentialsNotFoundException;
use App\Plugins\KelinMeter\Exceptions\KelinApiResponseException;
use App\Plugins\KelinMeter\Helpers\ApiHelpers;
use App\Plugins\KelinMeter\Models\KelinCredential;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class KelinMeterApiClient {
    public function __construct(
        private Client $client,
        private ApiHelpers $apiHelpers,
        private KelinCredential $credential,
    ) {}

    /**
     * @param array<string, mixed> $queryParams
     *
     * @return string|array<string, mixed>
     */
    public function token(string $url, array $queryParams): string|array {
        try {
            $credential = $this->getCredentials();
        } catch (\Exception $e) {
            throw new KelinApiCredentialsNotFoundException($e->getMessage());
        }
        try {
            $response = $this->client->request(
                'GET',
                $credential->api_url.$url.'?param='.urlencode(json_encode($queryParams))
            );
        } catch (GuzzleException $exception) {
            throw new KelinApiResponseException($exception->getMessage());
        }

        return $this->apiHelpers->checkApiResult(json_decode((string) $response->getBody(), true));
    }

    /**
     * @param array<string, mixed> $queryParams
     *
     * @return array<string, string|array<string, string|array<string, mixed>>>|string
     */
    public function get(string $url, ?array $queryParams = null): string|array {
        try {
            $credential = $this->getCredentials();
        } catch (ModelNotFoundException $e) {
            throw new KelinApiCredentialsNotFoundException($e->getMessage());
        }
        try {
            $requestingUri = $credential->api_url.$url.'?token='.$credential->authentication_token;
            if ($queryParams) {
                $requestingUri .= '&param='.urlencode(json_encode($queryParams));
            }
            // $test ='%7B%22meterType%22%3A1%2C%22startYmd%22%3A%2220200801%22%2C%22startHms%22%3A%220%22%2C%22endYmd%22%3A%2220200801%22%2C%22endHms%22%3A%22235900%22%2C%22pageNo%22%3A%221%22%2C%22pageSize%22%3A%22500%22%7D';
            // if ($queryParams) {
            //     $requestingUri .= '&param=' . $test;
            //  }
            $response = $this->client->request('GET', $requestingUri);
        } catch (GuzzleException $exception) {
            throw new KelinApiResponseException($exception->getMessage());
        }

        return $this->apiHelpers->checkApiResult(json_decode((string) $response->getBody(), true));
    }

    public function getCredentials(): KelinCredential {
        return $this->credential->newQuery()->firstOrFail();
    }
}
