<?php

namespace App\Plugins\CalinMeter\Http\Controllers;

use App\Plugins\CalinMeter\Http\Clients\CalinMeterApiClient;
use App\Plugins\CalinMeter\Http\Requests\CalinCredentialRequest;
use App\Plugins\CalinMeter\Http\Resources\CalinResource;
use App\Plugins\CalinMeter\Services\CalinCredentialService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

#[Group('Plugins / Calin Meter', 'API endpoints for integrating with Calin meters')]
class CalinCredentialController extends Controller {
    public function __construct(
        private CalinCredentialService $credentialService,
        private CalinMeterApiClient $calinMeterApiClient,
    ) {}

    public function show(): CalinResource {
        return new CalinResource($this->credentialService->getCredentials());
    }

    public function update(CalinCredentialRequest $request): CalinResource {
        $credentials = $this->credentialService->updateCredentials([
            'api_url' => $request->string('api_url')->toString(),
            'user_id' => $request->string('user_id')->toString(),
            'api_key' => $request->string('api_key')->toString(),
        ]);

        return new CalinResource($credentials);
    }

    /**
     * Confirm Calin accepts the entered login without saving it.
     */
    public function check(CalinCredentialRequest $request): JsonResponse {
        $this->calinMeterApiClient->checkCredentials(
            $request->string('api_url')->toString(),
            $request->string('user_id')->toString(),
            $request->string('api_key')->toString(),
        );

        return response()->json(['valid' => true]);
    }
}
