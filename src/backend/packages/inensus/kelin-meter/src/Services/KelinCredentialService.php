<?php

namespace Inensus\KelinMeter\Services;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Inensus\KelinMeter\Exceptions\KelinApiResponseException;
use Inensus\KelinMeter\Http\Clients\KelinMeterApiClient;
use Inensus\KelinMeter\Models\KelinCredential;

class KelinCredentialService {
    private $rootUrl = '/login';

    public function __construct(
        private KelinCredential $credential,
        private KelinMeterApiClient $kelinApi,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials() {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'username' => null,
            'password' => null,
            'is_authenticated' => 0,
            'api_url' => config('kelin-meter.root_url'),
            'authentication_token' => null,
        ]);
    }

    public function getCredentials() {
        return $this->credential->newQuery()->first();
    }

    public function updateCredentials($data) {
        $credential = $this->getCredentials();
        $credential->update([
            'username' => $data['username'],
            'password' => $data['password'],
        ]);

        try {
            $queryParams = [
                'userName' => $data['username'],
                'passWord' => $data['password'],
            ];
            $result = $this->kelinApi->token($this->rootUrl, $queryParams);
            $credential->update([
                'authentication_token' => $result['data']['token'],
                'is_authenticated' => true,
            ]);
        } catch (KelinApiResponseException $exception) {
            $credential->is_authenticated = false;
            $credential->authentication_token = null;
        } catch (GuzzleException $exception) {
            Log::critical(
                'Unknown exception while authenticating KelinMeter',
                ['reason' => $exception->getMessage()]
            );
            $credential->is_authenticated = false;
            $credential->authentication_token = null;
        }
        $credential->save();

        return $credential->fresh();
    }

    public function refreshAccessToken() {
        $credential = $this->getCredentials();
        if (!$credential || (!$credential->username && !$credential->password)) {
            Log::debug('KelinMeter credentials is not registered yet.');

            return;
        }
        try {
            $queryParams = [
                'userName' => $credential->username,
                'passWord' => $credential->password,
            ];

            $result = $this->kelinApi->token($this->rootUrl, $queryParams);
            $credential->update([
                'authentication_token' => $result['data']['token'],
                'is_authenticated' => true,
            ]);
        } catch (KelinApiResponseException $exception) {
            $credential->is_authenticated = false;
            $credential->authentication_token = null;
            Log::debug(
                'API error occurred while refreshing access token KelinMeter',
                ['reason' => $exception->getMessage()]
            );
        } catch (GuzzleException $exception) {
            Log::error(
                'Unknown exception while  refreshing access token KelinMeter',
                ['reason' => $exception->getMessage()]
            );
        }
    }
}
