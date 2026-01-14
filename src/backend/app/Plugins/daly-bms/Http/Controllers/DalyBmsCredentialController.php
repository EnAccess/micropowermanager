<?php

namespace Inensus\DalyBms\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\DalyBms\Http\Requests\DalyBmsCredentialRequest;
use Inensus\DalyBms\Http\Resources\DalyBmsResource;
use Inensus\DalyBms\Services\DalyBmsCredentialService;

class DalyBmsCredentialController extends Controller {
    public function __construct(
        private DalyBmsCredentialService $credentialService,
    ) {}

    public function show(): DalyBmsResource {
        return DalyBmsResource::make($this->credentialService->getCredentials());
    }

    public function update(DalyBmsCredentialRequest $request): DalyBmsResource {
        $credentials = $this->credentialService->getCredentials();
        $updateData = $request->only([
            'user_name',
            'password',
        ]);
        $credentials = $this->credentialService->updateCredentials($credentials, $updateData);

        return DalyBmsResource::make($credentials);
    }
}
