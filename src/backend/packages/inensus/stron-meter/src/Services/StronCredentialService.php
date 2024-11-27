<?php

namespace Inensus\StronMeter\Services;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Inensus\StronMeter\Http\Requests\StronMeterApiRequests;
use Inensus\StronMeter\Models\StronCredential;

class StronCredentialService {
    private $rootUrl = '/login/';
    private $credential;
    private $stronApi;

    public function __construct(
        StronCredential $credentialModel,
        StronMeterApiRequests $stronApi,
    ) {
        $this->credential = $credentialModel;
        $this->stronApi = $stronApi;
    }

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials() {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'username' => null,
            'password' => null,
            'is_authenticated' => 0,
            'api_url' => 'http://www.saitecapi.stronpower.com/api',
            'api_token' => null,
        ]);
    }

    public function getCredentials() {
        return $this->credential->newQuery()->first();
    }

    public function updateCredentials($data) {
        $credential = $this->credential->newQuery()->firstOrFail();

        $credential->update([
            'username' => $data['username'],
            'password' => $data['password'],
            'company_name' => $data['company_name'],
        ]);
        $postParams = [
            'Username' => $data['username'],
            'Password' => $data['password'],
            'Companyname' => $data['company_name'],
        ];

        try {
            $result = $this->stronApi->token($this->rootUrl, $postParams);

            $credential->update([
                'api_token' => $result,
                'is_authenticated' => true,
            ]);
        } catch (GuzzleException $gException) {
            if ($gException->getResponse()->getStatusCode() === 400) {
                $credential->is_authenticated = false;
                $credential->api_token = null;
            } else {
                $credential->is_authenticated = null;
                $credential->api_token = null;
            }
        } catch (\Exception $exception) {
            Log::critical(
                'Unknown exception while authenticating StronMeter',
                ['reason' => $exception->getMessage()]
            );
            $credential->is_authenticated = false;
            $credential->api_token = null;
        }
        $credential->save();

        return $credential->fresh();
    }
}
