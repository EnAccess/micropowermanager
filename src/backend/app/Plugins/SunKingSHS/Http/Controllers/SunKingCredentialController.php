<?php

namespace App\Plugins\SunKingSHS\Http\Controllers;

use App\Plugins\SunKingSHS\Http\Requests\SunKingCredentialRequest;
use App\Plugins\SunKingSHS\Http\Resources\SunKingResource;
use App\Plugins\SunKingSHS\Services\SunKingCredentialService;
use Illuminate\Routing\Controller;

class SunKingCredentialController extends Controller {
    public function __construct(
        private SunKingCredentialService $credentialService,
    ) {}

    public function show(): SunKingResource {
        return SunKingResource::make($this->credentialService->getCredentials());
    }

    public function update(SunKingCredentialRequest $request): SunKingResource {
        $credentials = $this->credentialService->getCredentials();
        $credentials = $this->credentialService->updateCredentials(
            $credentials,
            $request->only([
                'auth_url',
                'api_url',
                'client_id',
                'client_secret',
            ])
        );

        return SunKingResource::make($credentials);
    }
}
