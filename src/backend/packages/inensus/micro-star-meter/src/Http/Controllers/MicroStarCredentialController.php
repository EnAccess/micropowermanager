<?php

namespace Inensus\MicroStarMeter\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\MicroStarMeter\Http\Requests\MicroStarCredentialRequest;
use Inensus\MicroStarMeter\Http\Resources\MicroStarResource;
use Inensus\MicroStarMeter\Services\MicroStarCredentialService;

class MicroStarCredentialController extends Controller {
    public function __construct(private MicroStarCredentialService $credentialService) {}

    public function show(): MicroStarResource {
        return MicroStarResource::make($this->credentialService->getCredentials());
    }

    public function update(MicroStarCredentialRequest $request): MicroStarResource {
        $credentials = $this->credentialService->updateCredentials($request->only([
            'id',
            'api_url',
            'certificate_password',
        ]));

        return MicroStarResource::make($credentials);
    }
}
