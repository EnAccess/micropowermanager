<?php

namespace Inensus\GomeLongMeter\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\GomeLongMeter\Http\Requests\GomeLongCredentialRequest;
use Inensus\GomeLongMeter\Http\Resources\GomeLongResource;
use Inensus\GomeLongMeter\Services\GomeLongCredentialService;

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
