<?php

namespace Inensus\Prospect\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\Prospect\Http\Resources\ProspectResource;
use Inensus\Prospect\Services\ProspectCredentialService;

class ProspectCredentialController extends Controller {
    public function __construct(
        private ProspectCredentialService $credentialService,
    ) {}

    public function show() {
        $credentials = $this->credentialService->getCredentials();
        if ($credentials === null) {
            return new ProspectResource(null);
        }

        return ProspectResource::collection($credentials);
    }

    public function update(Request $request) {
        $request->validate([
            '*.id' => ['nullable', 'integer'],
            '*.api_url' => ['required', 'string'],
            '*.api_token' => ['required', 'string', 'min:3'],
        ]);

        $credentials = $this->credentialService->updateCredentials($request->all());

        return ProspectResource::collection($credentials);
    }
}
