<?php

namespace Inensus\StronMeter\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\StronMeter\Http\Requests\StronCredentialRequest;
use Inensus\StronMeter\Http\Resources\StronCredentialResource;
use Inensus\StronMeter\Http\Resources\StronResource;
use Inensus\StronMeter\Services\StronCredentialService;

class StronCredentialController extends Controller {
    private $credentialService;

    public function __construct(StronCredentialService $credentialService) {
        $this->credentialService = $credentialService;
    }

    public function show(): StronResource {
        return new StronResource($this->credentialService->getCredentials());
    }

    public function update(StronCredentialRequest $request): StronCredentialResource {
        $credentials = $this->credentialService->updateCredentials($request->only([
            'username',
            'password',
            'company_name',
        ]));

        return new StronCredentialResource($credentials);
    }
}
