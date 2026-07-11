<?php

namespace App\Plugins\StronMeter\Http\Controllers;

use App\Plugins\StronMeter\Http\Requests\StronCredentialRequest;
use App\Plugins\StronMeter\Http\Resources\StronCredentialResource;
use App\Plugins\StronMeter\Http\Resources\StronResource;
use App\Plugins\StronMeter\Services\StronCredentialService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Routing\Controller;

#[Group('Plugins / Stron Meter', 'API endpoints for integrating with Stron meters')]
class StronCredentialController extends Controller {
    public function __construct(private StronCredentialService $credentialService) {}

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
