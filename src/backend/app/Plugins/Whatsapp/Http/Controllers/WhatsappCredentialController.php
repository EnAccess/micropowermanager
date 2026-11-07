<?php

namespace App\Plugins\Whatsapp\Http\Controllers;

use App\Plugins\Whatsapp\Http\Requests\WhatsappCredentialRequest;
use App\Plugins\Whatsapp\Http\Resources\WhatsappCredentialResource;
use App\Plugins\Whatsapp\Services\WhatsappClient;
use App\Plugins\Whatsapp\Services\WhatsappCredentialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class WhatsappCredentialController extends Controller {
    public function __construct(
        private WhatsappCredentialService $credentialService,
        private WhatsappClient $client,
    ) {}

    public function show(): WhatsappCredentialResource {
        $credentials = $this->credentialService->getCredentials()
            ?? $this->credentialService->createCredentials();

        // the verify token is generated once, then shown to the operator for Meta's webhook setup
        if (empty($credentials->webhook_verify_token)) {
            $credentials = $this->credentialService->updateCredentials([
                'id' => $credentials->id,
                'webhook_verify_token' => Str::random(32),
            ]);
        }

        return WhatsappCredentialResource::make($credentials);
    }

    public function update(WhatsappCredentialRequest $request): WhatsappCredentialResource {
        // drop nulls so a blank field never overwrites a saved value
        $data = array_filter($request->validated(), fn ($value) => $value !== null);

        return WhatsappCredentialResource::make($this->credentialService->updateCredentials($data));
    }

    /**
     * "Test connection" button: asks Meta for the number's display name.
     */
    public function test(): JsonResponse {
        $credentials = $this->credentialService->getCredentials();

        if (!$credentials || !$credentials->access_token || !$credentials->phone_number_id) {
            return response()->json(['ok' => false, 'error' => 'Save your credentials first.'], 422);
        }

        return response()->json($this->client->checkCredentials($credentials));
    }

    /**
     * "Send test message" button.
     */
    public function sendTest(Request $request): JsonResponse {
        $request->validate([
            'phone' => ['required', 'string'],
            'use_hello_world' => ['nullable', 'boolean'],
        ]);

        $credentials = $this->credentialService->getCredentials();
        if (!$credentials || !$credentials->access_token || !$credentials->phone_number_id) {
            return response()->json(['ok' => false, 'error' => 'Save your credentials first.'], 422);
        }

        $phone = preg_replace('/\D/', '', $request->input('phone'));

        if ($request->boolean('use_hello_world')) {
            $result = $this->client->sendTemplate($credentials, $phone, 'hello_world', 'en_US', []);
        } else {
            $result = $this->client->sendTemplate(
                $credentials,
                $phone,
                $credentials->receipt_template,
                $credentials->template_language,
                ['This is a test message from MicroPowerManager.']
            );
        }

        return response()->json($result);
    }
}
