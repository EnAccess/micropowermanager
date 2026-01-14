<?php

namespace App\Plugins\SparkMeter\Http\Controllers;

use App\Plugins\SparkMeter\Http\Requests\SmCredentialRequest;
use App\Plugins\SparkMeter\Http\Resources\SparkMeterCredentialResource;
use App\Plugins\SparkMeter\Http\Resources\SparkResource;
use App\Plugins\SparkMeter\Services\CredentialService;
use Illuminate\Routing\Controller;

class SmCredentialController extends Controller {
    public function __construct(private CredentialService $credentialService) {}

    public function show(): SparkResource {
        return new SparkResource($this->credentialService->getCredentials());
    }

    public function update(SmCredentialRequest $request): SparkMeterCredentialResource {
        $credentials = $this->credentialService->updateCredentials($request->only([
            'id',
            'api_key',
            'api_secret',
        ]));

        return new SparkMeterCredentialResource($credentials);
    }
}
