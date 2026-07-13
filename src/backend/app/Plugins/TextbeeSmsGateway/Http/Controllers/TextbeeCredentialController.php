<?php

namespace App\Plugins\TextbeeSmsGateway\Http\Controllers;

use App\Plugins\TextbeeSmsGateway\Http\Requests\TextbeeCredentialRequest;
use App\Plugins\TextbeeSmsGateway\Http\Resources\TextbeeResource;
use App\Plugins\TextbeeSmsGateway\Services\TextbeeCredentialService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Routing\Controller;

#[Group('Plugins / Textbee SMS Gateway', 'API endpoints for integrating with the Textbee SMS gateway')]
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
        $webhookSecret = $request->input('webhook_secret');
        $id = $request->input('id');

        $credentials = $this->credentialService->updateCredentials([
            'id' => $id,
            'api_key' => $apiKey,
            'device_id' => $deviceId,
            'webhook_secret' => $webhookSecret,
        ]);

        return TextbeeResource::make($credentials);
    }
}
