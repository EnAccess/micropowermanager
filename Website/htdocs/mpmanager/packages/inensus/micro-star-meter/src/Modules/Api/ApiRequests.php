<?php

namespace Inensus\MicroStarMeter\Modules\Api;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Inensus\MicroStarMeter\Exceptions\MicroStarApiResponseException;
use Inensus\MicroStarMeter\Models\MicroStarCredential;
use Inensus\MicroStarMeter\Modules\Api\Utils\ResponseResolver;


class ApiRequests
{

    const PASSWORD = 'CertPass*1';

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
                    ],
                    'cert' => [__DIR__ . '/Certs/client.staging.p12', self::PASSWORD]
                ]);

            return $this->responseResolver->checkResponse(json_decode((string)$request->getBody(), true));
        } catch (GuzzleException|MicroStarApiResponseException $exception) {
            Log::critical('MicroStar API Transaction Failed', [
                'message :' => $exception->getMessage()
            ]);
            throw new MicroStarApiResponseException($exception->getMessage());
        }
    }

    public function testGet()
    {
        $url = 'https://ympt.microstarelectric.com';
        $meterList = '/TMRDataService/deviceInfo/device?skip=0&take=500';
        $meterInfo = '/TMRDataService/deviceInfo/device?deviceNo=0101189004654';
        $billingInfo = '/TMRDataService/billingData?deviceNo=0101189004654&dataRecordMonth=2022-10';
        $event ='/TMRDataService/eventInfo/event';
        $eventCode = '/TMRDataService/eventInfo/event?eventCode=1408';
        $token = '/TMRDataService/getStsVendingToken?deviceNo=0600140100281&customerAccount=123456789&sgc=600415&ti=2&rechargeAmount=100';
        try {
            $request = $this->httpClient->get($url . $meterList,
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                    'cert' => [__DIR__ . '/Certs/client.ympt.p12', self::PASSWORD]
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