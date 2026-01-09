<?php

namespace Inensus\ChintMeter\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\ChintMeter\Http\Requests\ChintCredentialRequest;
use Inensus\ChintMeter\Http\Resources\ChintResource;
use Inensus\ChintMeter\Services\ChintCredentialService;

class ChintCredentialController extends Controller {
    public function __construct(
        private ChintCredentialService $credentialService,
    ) {}

    public function show(): ChintResource {
        return ChintResource::make($this->credentialService->getCredentials());
    }

    public function update(ChintCredentialRequest $request): ChintResource {
        $credentials = $this->credentialService->getCredentials();
        $updateData = $request->only([
            'user_name',
            'user_password',
        ]);
        $credentials = $this->credentialService->updateCredentials($credentials, $updateData);

        return ChintResource::make($credentials);
    }
}
