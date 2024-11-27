<?php

namespace Inensus\KelinMeter\Http\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Inensus\KelinMeter\Exceptions\KelinApiCredentialsNotFoundException;
use Inensus\KelinMeter\Exceptions\KelinApiResponseException;
use Inensus\KelinMeter\Helpers\ApiHelpers;
use Inensus\KelinMeter\Models\KelinCredential;

class KelinMeterApiClient {
    private $client;
    private $apiHelpers;
    private $credential;

    public function __construct(
        Client $httpClient,
        ApiHelpers $apiHelpers,
        KelinCredential $credential,
    ) {
        $this->client = $httpClient;
        $this->apiHelpers = $apiHelpers;
        $this->credential = $credential;
    }

    public function token($url, $queryParams) {
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

    public function get($url, $queryParams = null) {
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
            /*  $test ='%7B%22meterType%22%3A1%2C%22startYmd%22%3A%2220200801%22%2C%22startHms%22%3A%220%22%2C%22endYmd%22%3A%2220200801%22%2C%22endHms%22%3A%22235900%22%2C%22pageNo%22%3A%221%22%2C%22pageSize%22%3A%22500%22%7D';
             if ($queryParams) {
                 $requestingUri .= '&param=' . $test;
             }*/
            $response = $this->client->request('GET', $requestingUri);
        } catch (GuzzleException $exception) {
            throw new KelinApiResponseException($exception->getMessage());
        }

        return $this->apiHelpers->checkApiResult(json_decode((string) $response->getBody(), true));
    }

    public function getCredentials() {
        return $this->credential->newQuery()->firstOrFail();
    }
}
