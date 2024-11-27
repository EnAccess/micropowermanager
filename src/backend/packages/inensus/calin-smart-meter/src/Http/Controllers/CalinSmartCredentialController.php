<?php

namespace Inensus\CalinSmartMeter\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\CalinSmartMeter\Http\Requests\CalinSmartCredentialRequest;
use Inensus\CalinSmartMeter\Http\Resources\CalinSmartResource;
use Inensus\CalinSmartMeter\Services\CalinSmartCredentialService;

class CalinSmartCredentialController extends Controller {
    private $credentialService;

    public function __construct(CalinSmartCredentialService $credentialService) {
        $this->credentialService = $credentialService;
    }

    public function show(): CalinSmartResource {
        return new CalinSmartResource($this->credentialService->getCredentials());
    }

    public function update(CalinSmartCredentialRequest $request): CalinSmartResource {
        $credentials = $this->credentialService->updateCredentials($request->only([
            'id',
            'company_name',
            'user_name',
            'password',
            'password_vend',
        ]));

        return new CalinSmartResource($credentials);
    }
}
