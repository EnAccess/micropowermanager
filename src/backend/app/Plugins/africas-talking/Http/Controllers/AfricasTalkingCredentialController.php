<?php

namespace Inensus\AfricasTalking\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\AfricasTalking\Http\Requests\AfricasTalkingCredentialRequest;
use Inensus\AfricasTalking\Http\Resources\AfricasTalkingResource;
use Inensus\AfricasTalking\Services\AfricasTalkingCredentialService;

class AfricasTalkingCredentialController extends Controller {
    public function __construct(
        private AfricasTalkingCredentialService $credentialService,
    ) {}

    public function show(): AfricasTalkingResource {
        return AfricasTalkingResource::make($this->credentialService->getCredentials());
    }

    public function update(AfricasTalkingCredentialRequest $request): AfricasTalkingResource {
        $apiKey = $request->input('api_key');
        $username = $request->input('username');
        $shortCode = $request->input('short_code');
        $id = $request->input('id');

        $credentials = $this->credentialService->updateCredentials([
            'id' => $id,
            'api_key' => $apiKey,
            'username' => $username,
            'short_code' => $shortCode,
        ]);

        return AfricasTalkingResource::make($credentials);
    }
}
