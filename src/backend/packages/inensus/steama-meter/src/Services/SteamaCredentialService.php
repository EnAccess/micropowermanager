<?php

namespace Inensus\SteamaMeter\Services;

use App\Traits\EncryptsCredentials;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use Inensus\SteamaMeter\Http\Clients\SteamaMeterApiClient;
use Inensus\SteamaMeter\Models\SteamaCredential;

class SteamaCredentialService {
    use EncryptsCredentials;
    private string $rootUrl = '/get-token/';

    public function __construct(
        private SteamaCredential $credential,
        private SteamaMeterApiClient $steamaApi,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials(): SteamaCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'username' => null,
            'password' => null,
            'is_authenticated' => 0,
            'api_url' => 'https://api.steama.co',
            'authentication_token' => null,
        ]);
    }

    public function getCredentials(): ?SteamaCredential {
        $credential = $this->credential->newQuery()->first();

        return $this->decryptCredentialFields($credential, ['username', 'password', 'authentication_token']);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateCredentials(array $data): SteamaCredential {
        $credential = $this->credential->newQuery()->find($data['id']);

        $encryptedData = $this->encryptCredentialFields($data, ['username', 'password']);
        $credential->update($encryptedData);
        $postParams = [
            'username' => $data['username'],
            'password' => $data['password'],
        ];
        try {
            $result = $this->steamaApi->token($this->rootUrl, $postParams);
            $credential->update([
                'authentication_token' => $this->encryptCredentialField($result['token']),
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
        $credential->fresh();

        return $this->decryptCredentialFields($credential, ['username', 'password', 'authentication_token']);
    }
}
