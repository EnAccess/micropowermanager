<?php

namespace App\Plugins\CalinMeterV2\Http\Controllers;

use App\Plugins\CalinMeterV2\Http\Clients\CalinMeterV2ApiClient;
use App\Plugins\CalinMeterV2\Http\Requests\CalinV2CredentialRequest;
use App\Plugins\CalinMeterV2\Http\Resources\CalinV2CredentialResource;
use App\Plugins\CalinMeterV2\Models\CalinV2Credential;
use App\Plugins\CalinMeterV2\Services\CalinV2CredentialService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

#[Group('Plugins / Calin Meter API V2', 'API endpoints for integrating with Calin API V2 systems')]
class CalinV2CredentialController extends Controller {
    public function __construct(
        private CalinV2CredentialService $credentialService,
        private CalinMeterV2ApiClient $apiClient,
    ) {}

    public function show(): CalinV2CredentialResource {
        return new CalinV2CredentialResource($this->credentialService->getCredentials() ?? $this->credentialService->createCredentials());
    }

    /**
     * Save the credentials once Calin accepts a login with them.
     */
    public function update(CalinV2CredentialRequest $request): CalinV2CredentialResource {
        return new CalinV2CredentialResource($this->credentialService->updateCredentials($request->credentials()));
    }

    /**
     * Confirm Calin accepts the entered login without saving it.
     */
    public function check(CalinV2CredentialRequest $request): JsonResponse {
        $this->apiClient->login(new CalinV2Credential($request->credentials()));

        return response()->json(['valid' => true]);
    }
}
