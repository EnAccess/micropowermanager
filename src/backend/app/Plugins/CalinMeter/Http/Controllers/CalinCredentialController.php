<?php

namespace App\Plugins\CalinMeter\Http\Controllers;

use App\Plugins\CalinMeter\Http\Requests\CalinCredentialRequest;
use App\Plugins\CalinMeter\Http\Resources\CalinResource;
use App\Plugins\CalinMeter\Services\CalinCredentialService;
use Illuminate\Routing\Controller;

class CalinCredentialController extends Controller {
    public function __construct(private CalinCredentialService $credentialService) {}

    public function show(): CalinResource {
        return new CalinResource($this->credentialService->getCredentials());
    }

    public function update(CalinCredentialRequest $request): CalinResource {
        $credentials = $this->credentialService->updateCredentials($request->only([
            'user_id',
            'api_key',
        ]));

        return new CalinResource($credentials);
    }
}
