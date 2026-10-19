<?php

namespace App\Plugins\SparkMeter\Http\Controllers;

use App\Plugins\SparkMeter\Http\Requests\SmCredentialRequest;
use App\Plugins\SparkMeter\Http\Resources\SparkMeterCredentialResource;
use App\Plugins\SparkMeter\Services\CredentialService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

#[Group('Plugins / Spark Meter', 'API endpoints for integrating with SparkMeter meters')]
class SmCredentialController extends Controller {
    public function __construct(private CredentialService $credentialService) {}

    public function show(): SparkMeterCredentialResource {
        return new SparkMeterCredentialResource(
            $this->credentialService->getCredentials() ?? $this->credentialService->createSmCredentials()
        );
    }

    public function check(SmCredentialRequest $request): JsonResponse {
        return response()->json($this->credentialService->checkCredentials($request->only([
            'api_url',
            'api_key',
            'api_secret',
        ])));
    }

    public function update(SmCredentialRequest $request): SparkMeterCredentialResource {
        $credentials = $this->credentialService->updateCredentials($request->only([
            'id',
            'api_url',
            'api_key',
            'api_secret',
        ]));

        return new SparkMeterCredentialResource($credentials);
    }
}
