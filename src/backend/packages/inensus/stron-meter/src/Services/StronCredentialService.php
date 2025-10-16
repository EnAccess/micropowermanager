<?php

namespace Inensus\StronMeter\Services;

use App\Traits\EncryptsCredentials;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use Inensus\StronMeter\Http\Requests\StronMeterApiRequests;
use Inensus\StronMeter\Models\StronCredential;

class StronCredentialService {
    use EncryptsCredentials;
    private string $rootUrl = '/login/';

    public function __construct(
        private StronCredential $credential,
        private StronMeterApiRequests $stronApi,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials(): StronCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'username' => null,
            'password' => null,
            'is_authenticated' => 0,
            'api_url' => 'http://www.saitecapi.stronpower.com/api',
            'api_token' => null,
        ]);
    }

    public function getCredentials(): StronCredential {
        $credential = $this->credential->newQuery()->first();

        if ($credential) {
            // Decrypt sensitive fields
            if ($credential->api_token) {
                $credential->api_token = $this->decryptCredentialField($credential->api_token);
            }
            if ($credential->company_name) {
                $credential->company_name = $this->decryptCredentialField($credential->company_name);
            }
            if ($credential->username) {
                $credential->username = $this->decryptCredentialField($credential->username);
            }
            if ($credential->password) {
                $credential->password = $this->decryptCredentialField($credential->password);
            }
        }

        return $credential;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateCredentials(array $data): StronCredential {
        $credential = $this->credential->newQuery()->firstOrFail();

        $encryptedData = $this->encryptCredentialFields($data, ['username', 'password', 'company_name']);

        $credential->update($encryptedData);
        $postParams = [
            'Username' => $data['username'],
            'Password' => $data['password'],
            'Companyname' => $data['company_name'],
        ];

        try {
            $result = $this->stronApi->token($this->rootUrl, $postParams);

            $credential->update([
                'api_token' => $this->encryptCredentialField($result),
                'is_authenticated' => true,
            ]);
        } catch (ClientException) {
            $credential->is_authenticated = false;
            $credential->api_token = null;
        } catch (\Exception $exception) {
            Log::critical(
                'Unknown exception while authenticating StronMeter',
                ['reason' => $exception->getMessage()]
            );
            $credential->is_authenticated = false;
            $credential->api_token = null;
        }
        $credential->save();
        $credential->fresh();

        return $this->decryptCredentialFields($credential, ['username', 'password', 'company_name', 'api_token']);
    }
}
