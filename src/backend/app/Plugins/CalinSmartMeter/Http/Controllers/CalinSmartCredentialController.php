<?php

namespace App\Plugins\CalinSmartMeter\Http\Controllers;

use App\Plugins\CalinSmartMeter\Http\Requests\CalinSmartCredentialRequest;
use App\Plugins\CalinSmartMeter\Http\Resources\CalinSmartResource;
use App\Plugins\CalinSmartMeter\Services\CalinSmartCredentialService;
use Illuminate\Routing\Controller;

class CalinSmartCredentialController extends Controller {
    public function __construct(private CalinSmartCredentialService $credentialService) {}

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
