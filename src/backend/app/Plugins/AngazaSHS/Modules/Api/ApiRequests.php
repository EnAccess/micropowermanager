<?php

namespace App\Plugins\AngazaSHS\Modules\Api;

use App\Plugins\AngazaSHS\Exceptions\AngazaApiResponseException;
use App\Plugins\AngazaSHS\Models\AngazaCredential;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ApiRequests {
    public function __construct(
        private Client $httpClient,
    ) {}

    /**
     * @param array<string, mixed> $params
     */
    public function get(AngazaCredential $credentials, array $params, string $slug): mixed {
        $url = $credentials->getApiUrl().$slug;
        foreach ($params as $key => $value) {
            $url .= $key.'='.$value.'&';
        }

        try {
            $request = $this->httpClient->get(
                $url,
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => $this->getBasicAuthHeader($credentials),
                    ],
                ]
            );

            return json_decode((string) $request->getBody(), true);
        } catch (\Exception $e) {
            Log::critical('Angaza API request failed', [
                'message :' => $e->getMessage(),
            ]);
            throw new AngazaApiResponseException($e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    public function put(AngazaCredential $credentials, array $params, string $slug): mixed {
        $url = $credentials->getApiUrl().$slug;
        try {
            $request = $this->httpClient->put(
                $url,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => $this->getBasicAuthHeader($credentials),
                    ],
                    'body' => json_encode($params),
                ]
            );

            return json_decode((string) $request->getBody(), true);
        } catch (\Exception $e) {
            Log::critical(
                'Angaza API Transaction Failed',
                [
                    'URL :' => $url,
                    'Body :' => json_encode($params),
                    'message :' => $e->getMessage(),
                ]
            );
            throw new AngazaApiResponseException($e->getMessage());
        }
    }

    private function getBasicAuthHeader(AngazaCredential $credentials): string {
        $username = $credentials->getClientId();
        $password = $credentials->getClientSecret();
        $credentials = $username.':'.$password;
        $base64EncodedCredentials = base64_encode($credentials);

        return 'Basic '.$base64EncodedCredentials;
    }
}
