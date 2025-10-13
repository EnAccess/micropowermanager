<?php

namespace Inensus\SteamaMeter\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\SteamaMeter\Http\Requests\SteamaCredentialRequest;
use Inensus\SteamaMeter\Http\Resources\SteamaCredentialResource;
use Inensus\SteamaMeter\Http\Resources\SteamaResource;
use Inensus\SteamaMeter\Services\SteamaCredentialService;

class SteamaCredentialController extends Controller {
    public function __construct(private SteamaCredentialService $credentialService) {}

    public function show(): SteamaResource {
        return new SteamaResource($this->credentialService->getCredentials());
    }

    public function update(SteamaCredentialRequest $request): SteamaCredentialResource {
        $credentials = $this->credentialService->updateCredentials($request->only([
            'id',
            'username',
            'password',
        ]));

        return new SteamaCredentialResource($credentials);
    }
}
