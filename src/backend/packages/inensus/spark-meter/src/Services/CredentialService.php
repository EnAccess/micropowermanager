<?php

namespace Inensus\SparkMeter\Services;

use App\Traits\EncryptsCredentials;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use Inensus\SparkMeter\Http\Requests\SparkMeterApiRequests;
use Inensus\SparkMeter\Models\SmCredential;

class CredentialService {
    use EncryptsCredentials;
    private string $rootUrl = '/organizations';

    public function __construct(
        private SparkMeterApiRequests $sparkMeterApiRequests,
        private SmCredential $smCredential,
        private OrganizationService $organizationService,
    ) {}

    public function getCredentials(): object {
        $credential = $this->smCredential->newQuery()->latest()->take(1)->first();

        return $this->decryptCredentialFields($credential, ['api_key', 'api_secret']);
    }

    public function createSmCredentials() {
        return $this->smCredential->newQuery()->firstOrCreate(['id' => 1], [
            'api_key' => null,
            'api_secret' => null,
            'is_authenticated' => 0,
        ]);
    }

    public function updateCredentials(array $data): object {
        $smCredentials = $this->smCredential->newQuery()->find($data['id']);
        $encryptedData = $this->encryptCredentialFields($data, ['api_key', 'api_secret']);
        $smCredentials->update($encryptedData);
        try {
            $result = $this->sparkMeterApiRequests->getFromKoios($this->rootUrl);
            $smCredentials->is_authenticated = true;
            $this->organizationService->createOrganization($result['organizations'][0]);
        } catch (ClientException $cException) {
            $smCredentials->is_authenticated = $cException->getResponse()->getStatusCode() === 401 ? false : null;
        } catch (\Exception $exception) {
            Log::critical('Unknown exception while authenticating SparkMeter', ['reason' => $exception->getMessage()]);
            $smCredentials->is_authenticated = null;
        }
        $smCredentials->save();

        return $this->decryptCredentialFields($smCredentials, ['api_key', 'api_secret']);
    }
}
