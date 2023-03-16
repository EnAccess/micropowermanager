<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class JetsonMiniGridProxyController extends Controller
{
    const INTERNAL_API_URL = 'http://172.18.0.1:3000/api/jetson';
    public function __construct(
        private Client $httpClient
    ) {
    }

    /**
     * @throws GuzzleException
     */
    public function proxy($miniGridId, $slug, $gate)
    {
       $companyId = $slug;
        try {
            $data = [
                'miniGridId' => $miniGridId,
                'companyId' => $companyId
            ];
            $this->httpClient->post(self::INTERNAL_API_URL.'/'.$gate,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'body' => json_encode($data),

                ]);
        } catch (GuzzleException $exception) {
            Log::critical("Error occurred on $gate", [
                'message :' => $exception->getMessage()
            ]);
            throw $exception;
        }
    }

}