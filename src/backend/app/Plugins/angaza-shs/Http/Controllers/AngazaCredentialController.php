<?php

namespace Inensus\AngazaSHS\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\AngazaSHS\Http\Requests\AngazaCredentialRequest;
use Inensus\AngazaSHS\Http\Resources\AngazaResource;
use Inensus\AngazaSHS\Services\AngazaCredentialService;

class AngazaCredentialController extends Controller {
    public function __construct(private AngazaCredentialService $credentialService) {}

    public function show(): AngazaResource {
        return AngazaResource::make($this->credentialService->getCredentials());
    }

    public function update(AngazaCredentialRequest $request): AngazaResource {
        $credentials = $this->credentialService->getCredentials();
        $updateData = $request->only([
            'client_id',
            'client_secret',
        ]);
        $credentials = $this->credentialService->updateCredentials($credentials, $updateData);

        return AngazaResource::make($credentials);
    }
}
