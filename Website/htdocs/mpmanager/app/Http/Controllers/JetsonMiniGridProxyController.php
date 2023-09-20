<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JetsonMiniGridProxyController extends Controller
{
    public const INTERNAL_API_URL = 'http://172.18.0.1:3000/api/jetson';
    public function __construct(
        private Client $httpClient
    ) {
    }

    /**
     * @throws GuzzleException
     */
    public function proxy(Request $request, $miniGridId, $slug, $gate)
    {
        $companyId = $slug;
        $efficiencyCurve = $request->get('efficiencyCurve');
        try {
            $data = [
                'miniGridId' => $miniGridId,
                'companyId' => $companyId,
                'efficiencyCurve' => json_encode($efficiencyCurve),
                'socVal' => $request->get('socVal'),
                'consumptionCapacity' => $request->get('consumptionCapacity'),
            ];

            $this->httpClient->post(
                self::INTERNAL_API_URL . '/' . $gate,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'body' => json_encode($data),

                ]
            );
        } catch (GuzzleException $exception) {
            Log::critical("Error occurred on $gate", [
                'message :' => $exception->getMessage()
            ]);
            throw $exception;
        }
    }
}
