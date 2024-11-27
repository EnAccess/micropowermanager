<?php

namespace Inensus\SparkMeter\Services;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Inensus\SparkMeter\Http\Requests\SparkMeterApiRequests;
use Inensus\SparkMeter\Models\SmCredential;

class CredentialService {
    private $sparkMeterApiRequests;
    private $smCredential;
    private $smTableEncryption;
    private $rootUrl = '/organizations';
    private $organizationService;

    public function __construct(
        SparkMeterApiRequests $sparkMeterApiRequests,
        SmCredential $smCredential,
        OrganizationService $organizationService,
    ) {
        $this->sparkMeterApiRequests = $sparkMeterApiRequests;
        $this->smCredential = $smCredential;
        $this->organizationService = $organizationService;
    }

    public function getCredentials() {
        return $this->smCredential->newQuery()->latest()->take(1)->get()->first();
    }

    public function createSmCredentials() {
        return $this->smCredential->newQuery()->firstOrCreate(['id' => 1], [
            'api_key' => null,
            'api_secret' => null,
            'is_authenticated' => 0,
        ]);
    }

    public function updateCredentials($data) {
        $smCredentials = $this->smCredential->newQuery()->find($data['id']);
        $smCredentials->update([
            'api_key' => $data['api_key'],
            'api_secret' => $data['api_secret'],
        ]);
        try {
            $result = $this->sparkMeterApiRequests->getFromKoios($this->rootUrl);
            $smCredentials->is_authenticated = true;
            $this->organizationService->createOrganization($result['organizations'][0]);
        } catch (GuzzleException $gException) {
            if ($gException->getResponse()->getStatusCode() === 401) {
                $smCredentials->is_authenticated = false;
            } else {
                $smCredentials->is_authenticated = null;
            }
        } catch (\Exception $exception) {
            Log::critical('Unknown exception while authenticating SparkMeter', ['reason' => $exception->getMessage()]);
            $smCredentials->is_authenticated = null;
        }
        $smCredentials->save();

        return $smCredentials->fresh();
    }
}
