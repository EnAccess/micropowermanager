<?php

namespace Inensus\SteamaMeter\Services;

use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use Inensus\SteamaMeter\Http\Clients\SteamaMeterApiClient;
use Inensus\SteamaMeter\Models\SteamaCredential;

class SteamaCredentialService {
    private string $rootUrl = '/get-token/';

    public function __construct(private SteamaCredential $credential, private SteamaMeterApiClient $steamaApi) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials() {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'username' => null,
            'password' => null,
            'is_authenticated' => 0,
            'api_url' => 'https://api.steama.co',
            'authentication_token' => null,
        ]);
    }

    public function getCredentials() {
        return $this->credential->newQuery()->first();
    }

    public function updateCredentials(array $data) {
        $credential = $this->credential->newQuery()->find($data['id']);

        $credential->update([
            'username' => $data['username'],
            'password' => $data['password'],
        ]);
        $postParams = [
            'username' => $data['username'],
            'password' => $data['password'],
        ];
        try {
            $result = $this->steamaApi->token($this->rootUrl, $postParams);
            $credential->update([
                'authentication_token' => $result['token'],
                'is_authenticated' => true,
            ]);
        } catch (ClientException $cException) {
            if ($cException->getResponse()->getStatusCode() === 400) {
                $credential->is_authenticated = false;
                $credential->authentication_token = null;
            } else {
                $credential->is_authenticated = null;
                $credential->authentication_token = null;
            }
        } catch (\Exception $exception) {
            Log::critical(
                'Unknown exception while authenticating StemacoMeter',
                ['reason' => $exception->getMessage()]
            );
            $credential->is_authenticated = false;
            $credential->authentication_token = null;
        }
        $credential->save();

        return $credential->fresh();
    }
}
