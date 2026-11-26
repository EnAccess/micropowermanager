<?php

namespace App\Plugins\ChintMeter\Http\Controllers;

use App\Plugins\ChintMeter\Http\Requests\ChintCredentialRequest;
use App\Plugins\ChintMeter\Http\Resources\ChintResource;
use App\Plugins\ChintMeter\Services\ChintCredentialService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Routing\Controller;

#[Group('Plugins / Chint Meter', 'API endpoints for integrating with Chint meters')]
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
            'api_url',
            'user_name',
            'user_password',
            'tariff_applied_by',
        ]);
        $credentials = $this->credentialService->updateCredentials($credentials, $updateData);

        return ChintResource::make($credentials);
    }
}
