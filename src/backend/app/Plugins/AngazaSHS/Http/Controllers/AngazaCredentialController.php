<?php

namespace App\Plugins\AngazaSHS\Http\Controllers;

use App\Plugins\AngazaSHS\Http\Requests\AngazaCredentialRequest;
use App\Plugins\AngazaSHS\Http\Resources\AngazaResource;
use App\Plugins\AngazaSHS\Services\AngazaCredentialService;
use Illuminate\Routing\Controller;

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
