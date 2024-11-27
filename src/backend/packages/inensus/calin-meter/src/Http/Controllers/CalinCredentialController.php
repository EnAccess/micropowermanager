<?php

namespace Inensus\CalinMeter\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\CalinMeter\Http\Requests\CalinCredentialRequest;
use Inensus\CalinMeter\Http\Resources\CalinResource;
use Inensus\CalinMeter\Services\CalinCredentialService;

class CalinCredentialController extends Controller {
    private $credentialService;

    public function __construct(CalinCredentialService $credentialService) {
        $this->credentialService = $credentialService;
    }

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
