<?php

namespace App\Plugins\SteamaMeter\Http\Controllers;

use App\Plugins\SteamaMeter\Http\Requests\SteamaCredentialRequest;
use App\Plugins\SteamaMeter\Http\Resources\SteamaCredentialResource;
use App\Plugins\SteamaMeter\Http\Resources\SteamaResource;
use App\Plugins\SteamaMeter\Services\SteamaCredentialService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Routing\Controller;

#[Group('Plugins / Steama Meter', 'API endpoints for integrating with SteamaCo meters')]
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
