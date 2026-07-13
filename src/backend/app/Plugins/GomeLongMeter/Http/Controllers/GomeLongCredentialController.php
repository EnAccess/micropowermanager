<?php

namespace App\Plugins\GomeLongMeter\Http\Controllers;

use App\Plugins\GomeLongMeter\Http\Requests\GomeLongCredentialRequest;
use App\Plugins\GomeLongMeter\Http\Resources\GomeLongResource;
use App\Plugins\GomeLongMeter\Services\GomeLongCredentialService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Routing\Controller;

#[Group('Plugins / Gome Long Meter', 'API endpoints for integrating with GomeLong meters')]
class GomeLongCredentialController extends Controller {
    public function __construct(
        private GomeLongCredentialService $credentialService,
    ) {}

    public function show(): GomeLongResource {
        return GomeLongResource::make($this->credentialService->getCredentials());
    }

    public function update(GomeLongCredentialRequest $request): GomeLongResource {
        $credentials = $this->credentialService->getCredentials();
        $updateData = $request->only([
            'user_id',
            'user_password',
        ]);
        $credentials = $this->credentialService->updateCredentials($credentials, $updateData);

        return GomeLongResource::make($credentials);
    }
}
