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
        $data = [
            'phone_number_id' => $request->string('phone_number_id')->toString(),
            'business_account_id' => $request->string('business_account_id')->toString(),
            'access_token' => $request->filled('access_token') ? $request->string('access_token')->toString() : null,
            'app_secret' => $request->filled('app_secret') ? $request->string('app_secret')->toString() : null,
            'graph_api_version' => $request->string('graph_api_version')->toString(),
            'sms_fallback_enabled' => $request->boolean('sms_fallback_enabled'),
        ];

        // Cost settings are only touched when sent, so callers that predate them do not wipe the stored rates; an explicit null clears one.
        foreach (['sms_unit_cost', 'whatsapp_utility_cost'] as $costField) {
            if ($request->has($costField)) {
                $data[$costField] = $request->filled($costField) ? $request->float($costField) : null;
            }
        }
        if ($request->has('cost_currency')) {
            $data['cost_currency'] = $request->filled('cost_currency') ? $request->string('cost_currency')->upper()->toString() : null;
        }

        return WhatsappCredentialResource::make($this->credentialService->updateCredentials($data));
    }

    public function testConnection(): JsonResponse {
        return response()->json(['data' => $this->credentialService->testConnection()]);
    }
}
