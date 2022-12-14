<?php

namespace Inensus\SunKingMeter\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\SunKingMeter\Http\Requests\SunKingCredentialRequest;
use Inensus\SunKingMeter\Http\Resources\SunKingResource;
use Inensus\SunKingMeter\Services\SunKingCredentialService;


class SunKingCredentialController extends Controller
{

    public function __construct(private SunKingCredentialService $credentialService)
    {
    }

    public function show(): SunKingResource
    {
        return SunKingResource::make($this->credentialService->getCredentials());
    }

    public function update(SunKingCredentialRequest $request): SunKingResource
    {
        $credentials = $this->credentialService->getCredentials();
        $updateData = $request->only([
            'client_id',
            'client_secret'
        ]);
        $credentials = $this->credentialService->updateCredentials($credentials, $updateData);

        return SunKingResource::make($credentials);
    }
}
