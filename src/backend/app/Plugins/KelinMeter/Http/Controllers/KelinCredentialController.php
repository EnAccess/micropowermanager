<?php

namespace App\Plugins\KelinMeter\Http\Controllers;

use App\Plugins\KelinMeter\Http\Requests\KelinCredentialRequest;
use App\Plugins\KelinMeter\Http\Resources\KelinCredentialResource;
use App\Plugins\KelinMeter\Services\KelinCredentialService;
use Illuminate\Routing\Controller;

class KelinCredentialController extends Controller {
    public function __construct(private KelinCredentialService $credentialService) {}

    public function show(): KelinCredentialResource {
        return new KelinCredentialResource($this->credentialService->getCredentials());
    }

    public function update(KelinCredentialRequest $request): KelinCredentialResource {
        return new KelinCredentialResource($this->credentialService->updateCredentials($request->only([
            'id',
            'username',
            'password',
        ])));
    }
}
