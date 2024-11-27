<?php

namespace Inensus\SparkMeter\Http\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Inensus\SparkMeter\Exceptions\SparkAPIResponseException;
use Inensus\SparkMeter\Helpers\ResultStatusChecker;
use Inensus\SparkMeter\Models\SmCredential;
use Inensus\SparkMeter\Models\SmSite;

class SparkMeterApiRequests {
    private $client;
    private $resultStatusChecker;
    private $credential;
    private $site;
    private $smCredentail;

    public function __construct(
        Client $httpClient,
        ResultStatusChecker $resultStatusChecker,
        SmCredential $credential,
        SmSite $site,
        SmCredential $smCredential,
    ) {
        $this->client = $httpClient;
        $this->resultStatusChecker = $resultStatusChecker;
        $this->credential = $credential;
        $this->site = $site;
        $this->smCredentail = $smCredential;
    }

    public function get($url, $siteId) {
        $smSite = $this->getThunderCloudInformation($siteId);
        try {
            $request = $this->client->get(
                $smSite->thundercloud_url.$url,
                [
                    'headers' => [
                        'Content-Type' => 'application/json;charset=utf-8',
                        'Authentication-Token' => $smSite->thundercloud_token,
                    ],
                ]
            );
        } catch (GuzzleException $exception) {
            throw new SparkAPIResponseException($exception->getMessage());
        }

        return $this->resultStatusChecker->checkApiResult(json_decode((string) $request->getBody(), true));
    }

    public function post($url, $postParams, $siteId) {
        $smSite = $this->getThunderCloudInformation($siteId);
        try {
            $request = $this->client->post(
                $smSite->thundercloud_url.$url,
                [
                    'body' => $postParams ? json_encode($postParams) : null,
                    'headers' => [
                        'Content-Type' => 'application/json;charset=utf-8',
                        'Authentication-Token' => $smSite->thundercloud_token,
                    ],
                ]
            );
        } catch (GuzzleException $exception) {
            throw new SparkAPIResponseException($exception->getMessage());
        }

        return $this->resultStatusChecker->checkApiResult(json_decode((string) $request->getBody(), true));
    }

    public function put($url, $putParams, $siteId) {
        $smSite = $this->getThunderCloudInformation($siteId);
        try {
            $request = $this->client->put(
                $smSite->thundercloud_url.$url,
                [
                    'body' => json_encode($putParams),
                    'headers' => [
                        'Content-Type' => 'application/json;charset=utf-8',
                        'Authentication-Token' => $smSite->thundercloud_token,
                    ],
                ]
            );
        } catch (GuzzleException $exception) {
            throw new SparkAPIResponseException($exception->getMessage());
        }

        return $this->resultStatusChecker->checkApiResult(json_decode((string) $request->getBody(), true));
    }

    public function getByParams($url, $params, $siteId) {
        try {
            $smSite = $this->getThunderCloudInformation($siteId);
            $apiUrl = $smSite->thundercloud_url.$url.'?';
            foreach ($params as $key => $value) {
                $apiUrl .= $key.'='.$value.'&';
            }
            $apiUrl = substr($apiUrl, 0, -1);

            $request = $this->client->get(
                $apiUrl,
                [
                    'headers' => [
                        'Content-Type' => 'application/json;charset=utf-8',
                        'Authentication-Token' => $smSite->thundercloud_token,
                    ],
                ]
            );

            return json_decode((string) $request->getBody(), true);
        } catch (\Exception $e) {
            return [
                'status' => 'failure',
            ];
        }
    }

    public function getInfo($url, $id, $siteId) {
        $smSite = $this->getThunderCloudInformation($siteId);
        $apiUrl = $smSite->thundercloud_url.$url.$id;
        try {
            $request = $this->client->get(
                $apiUrl,
                [
                    'headers' => [
                        'Content-Type' => 'application/json;charset=utf-8',
                        'Authentication-Token' => $smSite->thundercloud_token,
                    ],
                ]
            );
        } catch (GuzzleException $exception) {
            throw new SparkAPIResponseException($exception->getMessage());
        }

        return $this->resultStatusChecker->checkApiResult(json_decode((string) $request->getBody(), true));
    }

    public function getFromKoios($url) {
        $smCredential = $this->getCredentials();
        try {
            $request = $this->client->get(
                $smCredential->api_url.$url,
                [
                    'headers' => [
                        'Content-Type' => 'application/json;charset=utf-8',
                        'X-API-KEY' => $smCredential->api_key,
                        'X-API-SECRET' => $smCredential->api_secret,
                    ],
                ]
            );
        } catch (GuzzleException $exception) {
            throw new SparkAPIResponseException($exception->getMessage());
        }

        return $this->resultStatusChecker->checkApiResult(json_decode((string) $request->getBody(), true));
    }

    public function postToKoios($url, $postParams) {
        $smCredential = $this->getCredentials();
        try {
            $request = $this->client->post(
                $smCredential->api_url.$url,
                [
                    'body' => json_encode($postParams),
                    'headers' => [
                        'Content-Type' => 'application/json;charset=utf-8',
                        'X-API-KEY' => $smCredential->api_key,
                        'X-API-SECRET' => $smCredential->api_secret,
                    ],
                ]
            );
        } catch (GuzzleException $exception) {
            throw new SparkAPIResponseException($exception->getMessage());
        }

        return $this->resultStatusChecker->checkApiResult(json_decode((string) $request->getBody(), true));
    }

    private function getCredentials() {
        return $this->smCredentail->newQuery()->first();
    }

    private function getThunderCloudInformation($siteId) {
        return $this->site->newQuery()->where('site_id', $siteId)->first();
    }
}
