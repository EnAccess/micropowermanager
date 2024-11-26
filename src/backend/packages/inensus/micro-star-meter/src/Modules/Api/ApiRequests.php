<?php

namespace Inensus\MicroStarMeter\Modules\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Inensus\MicroStarMeter\Exceptions\MicroStarApiResponseException;
use Inensus\MicroStarMeter\Models\MicroStarCredential;
use Inensus\MicroStarMeter\Modules\Api\Utils\ResponseResolver;

class ApiRequests {
    public function __construct(
        private Client $httpClient,
        private ResponseResolver $responseResolver,
    ) {}

    public function get(MicroStarCredential $credentials, array $params, string $slug) {
        $url = $credentials->getApiUrl().$slug;
        foreach ($params as $key => $value) {
            $url .= $key.'='.$value.'&';
        }
        $certificatePath = storage_path('app'.$credentials->certificate_path.'/'.
            $credentials->certificate_file_name);
        try {
            $request = $this->httpClient->get(
                $url,
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                    'cert' => [$certificatePath, $credentials->certificate_password],
                ]
            );

            return $this->responseResolver->checkResponse(json_decode((string) $request->getBody(), true));
        } catch (GuzzleException|MicroStarApiResponseException $exception) {
            Log::critical('MicroStar API Transaction Failed', [
                'message :' => $exception->getMessage(),
            ]);
            throw new MicroStarApiResponseException($exception->getMessage());
        }
    }

    public function testGet() {
        // ti1 = 1 phase 2 = 3 phase

        $url = 'https://ympt.microstarelectric.com';
        $meterList = '/TMRDataService/deviceInfo/device?skip=0&take=500';
        $meterInfo = '/TMRDataService/deviceInfo/device?deviceNo=0101189004654';
        $billingInfo = '/TMRDataService/billingData?deviceNo=0101189004654&dataRecordMonth=2022-10';
        $event = '/TMRDataService/eventInfo/event';
        $eventCode = '/TMRDataService/eventInfo/event?eventCode=1408';
        $token =
            '/TMRDataService/getStsVendingToken?deviceNo=0101189000116&ti=1&rechargeAmount=1';
        $customerList = '/TMRDataService/customer/customer?skip=0&take=500';
        $t = '/TMRDataService/getStsVendingToken?deviceNo=0101189000116&sgc=600415&ti=2&rechargeAmount=1';

        try {
            $request = $this->httpClient->get(
                $url.$t,
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                    'cert' => [__DIR__.'/Certs/client.ympt.p12', 'U7i8o9p0'],
                ]
            );

            return $this->responseResolver->checkResponse(json_decode((string) $request->getBody(), true));
        } catch (GuzzleException|MicroStarApiResponseException $exception) {
            Log::critical('MicroStar API Transaction Failed', [
                'message :' => $exception->getMessage(),
            ]);
            throw new MicroStarApiResponseException($exception->getMessage());
        }
    }
}
