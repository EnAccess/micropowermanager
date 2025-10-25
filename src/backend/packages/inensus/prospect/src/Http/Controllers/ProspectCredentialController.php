<?php

namespace Inensus\Prospect\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\Prospect\Http\Requests\ProspectCredentialRequest;
use Inensus\Prospect\Http\Resources\ProspectResource;
use Inensus\Prospect\Services\ProspectCredentialService;

class ProspectCredentialController extends Controller {
    public function __construct(
        private ProspectCredentialService $credentialService,
    ) {}

    public function show(): ProspectResource {
        return ProspectResource::make($this->credentialService->getCredentials());
    }

    public function update(ProspectCredentialRequest $request): ProspectResource {
        $apiUrl = $request->input('api_url');
        $apiToken = $request->input('api_token');
        $id = $request->input('id');

        $credentials = $this->credentialService->updateCredentials([
            'id' => $id,
            'api_url' => $apiUrl,
            'api_token' => $apiToken,
        ]);

        return ProspectResource::make($credentials);
    }
}
