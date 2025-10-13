<?php

namespace Inensus\SparkMeter\Services;

use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use Inensus\SparkMeter\Http\Requests\SparkMeterApiRequests;
use Inensus\SparkMeter\Models\SmCredential;

class CredentialService {
    private string $rootUrl = '/organizations';

    public function __construct(
        private SparkMeterApiRequests $sparkMeterApiRequests,
        private SmCredential $smCredential,
        private OrganizationService $organizationService,
    ) {}

    public function getCredentials() {
        return $this->smCredential->newQuery()->latest()->take(1)->first();
    }

    public function createSmCredentials() {
        return $this->smCredential->newQuery()->firstOrCreate(['id' => 1], [
            'api_key' => null,
            'api_secret' => null,
            'is_authenticated' => 0,
        ]);
    }

    public function updateCredentials(array $data) {
        $smCredentials = $this->smCredential->newQuery()->find($data['id']);
        $smCredentials->update([
            'api_key' => $data['api_key'],
            'api_secret' => $data['api_secret'],
        ]);
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

        return $smCredentials->fresh();
    }
}
