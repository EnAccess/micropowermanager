<?php

namespace App\Plugins\ChintMeter\Http\Controllers;

use App\Plugins\ChintMeter\Http\Requests\ChintCredentialRequest;
use App\Plugins\ChintMeter\Http\Resources\ChintResource;
use App\Plugins\ChintMeter\Services\ChintCredentialService;
use Illuminate\Routing\Controller;

class ChintCredentialController extends Controller {
    public function __construct(
        private ChintCredentialService $credentialService,
    ) {}

    public function show(): ChintResource {
        return ChintResource::make($this->credentialService->getCredentials());
    }

    public function update(ChintCredentialRequest $request): ChintResource {
        $credentials = $this->credentialService->getCredentials();
        $updateData = $request->only([
            'user_name',
            'user_password',
        ]);
        $credentials = $this->credentialService->updateCredentials($credentials, $updateData);

        return ChintResource::make($credentials);
    }
}
