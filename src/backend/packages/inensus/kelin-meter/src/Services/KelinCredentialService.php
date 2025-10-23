<?php

namespace Inensus\KelinMeter\Services;

use App\Traits\EncryptsCredentials;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Inensus\KelinMeter\Exceptions\KelinApiResponseException;
use Inensus\KelinMeter\Http\Clients\KelinMeterApiClient;
use Inensus\KelinMeter\Models\KelinCredential;

class KelinCredentialService {
    use EncryptsCredentials;
    private string $rootUrl = '/login';

    public function __construct(
        private KelinCredential $credential,
        private KelinMeterApiClient $kelinApi,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials(): KelinCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'username' => null,
            'password' => null,
            'is_authenticated' => 0,
            'api_url' => config('kelin-meter.root_url'),
            'authentication_token' => null,
        ]);
    }

    public function getCredentials(): ?KelinCredential {
        $credential = $this->credential->newQuery()->first();

        if ($credential) {
            // Decrypt sensitive fields
            if ($credential->username) {
                $credential->username = $this->decryptCredentialField($credential->username);
            }
            if ($credential->password) {
                $credential->password = $this->decryptCredentialField($credential->password);
            }
            if ($credential->authentication_token) {
                $credential->authentication_token = $this->decryptCredentialField($credential->authentication_token);
            }
        }

        return $credential;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateCredentials(array $data): KelinCredential {
        $credential = $this->getCredentials();
        $encryptedData = $this->encryptCredentialFields($data, ['username', 'password']);
        $credential->update($encryptedData);

        try {
            $queryParams = [
                'userName' => $data['username'],
                'passWord' => $data['password'],
            ];
            $result = $this->kelinApi->token($this->rootUrl, $queryParams);
            $credential->update([
                'authentication_token' => $this->encryptCredentialField($result['data']['token']),
                'is_authenticated' => true,
            ]);
        } catch (KelinApiResponseException) {
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
        $credential->fresh();

        return $this->decryptCredentialFields($credential, ['username', 'password', 'authentication_token']);
    }

    public function refreshAccessToken(): void {
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
                'authentication_token' => $this->encryptCredentialField($result['data']['token']),
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
