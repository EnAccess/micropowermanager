<?php

namespace App\Plugins\SunKingSHS\Http\Controllers;

use App\Plugins\SunKingSHS\Exceptions\SunKingApiResponseException;
use App\Plugins\SunKingSHS\Http\Clients\SunKingSHSApiClient;
use App\Plugins\SunKingSHS\Http\Requests\SunKingCredentialRequest;
use App\Plugins\SunKingSHS\Http\Resources\SunKingResource;
use App\Plugins\SunKingSHS\Models\SunKingCredential;
use App\Plugins\SunKingSHS\Services\SunKingCredentialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class SunKingCredentialController extends Controller {
    public function __construct(
        private SunKingCredentialService $credentialService,
        private SunKingSHSApiClient $apiClient,
    ) {}

    public function show(): SunKingResource {
        return SunKingResource::make($this->credentialService->getCredentials());
    }

    public function update(SunKingCredentialRequest $request): SunKingResource {
        $credentials = $this->credentialService->getCredentials();
        $credentials = $this->credentialService->updateCredentials(
            $credentials,
            $request->only([
                'auth_url',
                'api_url',
                'client_id',
                'client_secret',
            ])
        );

        return SunKingResource::make($credentials);
    }

    public function check(): JsonResponse {
        $credentials = $this->credentialService->getCredentials();

        if (!$credentials instanceof SunKingCredential) {
            return response()->json(['valid' => false]);
        }

        try {
            $this->apiClient->authentication($credentials);
        } catch (SunKingApiResponseException) {
            return response()->json(['valid' => false]);
        }

        return response()->json(['valid' => true]);
    }
}
