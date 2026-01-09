<?php

namespace Inensus\TextbeeSmsGateway\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\TextbeeSmsGateway\Http\Requests\TextbeeCredentialRequest;
use Inensus\TextbeeSmsGateway\Http\Resources\TextbeeResource;
use Inensus\TextbeeSmsGateway\Services\TextbeeCredentialService;

class TextbeeCredentialController extends Controller {
    public function __construct(
        private TextbeeCredentialService $credentialService,
    ) {}

    public function show(): TextbeeResource {
        return TextbeeResource::make($this->credentialService->getCredentials());
    }

    public function update(TextbeeCredentialRequest $request): TextbeeResource {
        $apiKey = $request->input('api_key');
        $deviceId = $request->input('device_id');
        $id = $request->input('id');

        $credentials = $this->credentialService->updateCredentials([
            'id' => $id,
            'api_key' => $apiKey,
            'device_id' => $deviceId,
        ]);

        return TextbeeResource::make($credentials);
    }
}
