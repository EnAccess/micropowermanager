<?php

namespace App\Plugins\WhatsappMessaging\Http\Controllers;

use App\Plugins\WhatsappMessaging\Http\Requests\WhatsappCredentialRequest;
use App\Plugins\WhatsappMessaging\Http\Resources\WhatsappCredentialResource;
use App\Plugins\WhatsappMessaging\Services\WhatsappCredentialService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

#[Group('Plugins / WhatsApp Messaging')]
class WhatsappCredentialController extends Controller {
    public function __construct(
        private WhatsappCredentialService $credentialService,
    ) {}

    public function show(): WhatsappCredentialResource {
        return WhatsappCredentialResource::make($this->credentialService->getCredentials());
    }

    public function update(WhatsappCredentialRequest $request): WhatsappCredentialResource {
        $credentials = $this->credentialService->updateCredentials([
            'phone_number_id' => $request->string('phone_number_id')->toString(),
            'business_account_id' => $request->string('business_account_id')->toString(),
            'access_token' => $request->filled('access_token') ? $request->string('access_token')->toString() : null,
            'app_secret' => $request->filled('app_secret') ? $request->string('app_secret')->toString() : null,
            'graph_api_version' => $request->string('graph_api_version')->toString(),
            'sms_fallback_enabled' => $request->boolean('sms_fallback_enabled'),
        ]);

        return WhatsappCredentialResource::make($credentials);
    }

    public function testConnection(): JsonResponse {
        return response()->json(['data' => $this->credentialService->testConnection()]);
    }
}
