<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappBusinessApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappCredential;
use App\Plugins\WhatsappBusinessApi\Services\WhatsappCredentialService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Plugins / WhatsApp Business API', 'API endpoints for WhatsApp Business API integration')]
class WhatsappCredentialController extends Controller {
    public function __construct(
        private WhatsappCredentialService $credentialService,
    ) {}

    public function get(): JsonResponse {
        $credentials = $this->credentialService->getCredentials();

        if (!$credentials instanceof WhatsappCredential) {
            $credentials = $this->credentialService->createCredentials();
        }

        return response()->json([
            'data' => $this->maskCredentials($credentials),
        ], 200);
    }

    public function update(Request $request): JsonResponse {
        $validatedData = $request->validate([
            'id' => ['required', 'integer'],
            'access_token' => ['nullable', 'string'],
            'phone_number_id' => ['nullable', 'string'],
            'waba_id' => ['nullable', 'string'],
            'webhook_secret' => ['nullable', 'string'],
            'app_secret' => ['nullable', 'string'],
            'transaction_confirmation_template' => ['nullable', 'string'],
            'reminder_template' => ['nullable', 'string'],
        ]);

        $credentials = $this->credentialService->updateCredentials($validatedData);

        return response()->json(['data' => $this->maskCredentials($credentials)], 200);
    }

    /**
     * @return array<string, mixed>
     */
    private function maskCredentials(WhatsappCredential $credentials): array {
        return [
            'id' => $credentials->id,
            'access_token' => $this->maskSecret($credentials->access_token),
            'phone_number_id' => $credentials->phone_number_id,
            'waba_id' => $credentials->waba_id,
            'webhook_secret' => $this->maskSecret($credentials->webhook_secret),
            'app_secret' => $this->maskSecret($credentials->app_secret),
            'transaction_confirmation_template' => $credentials->transaction_confirmation_template,
            'reminder_template' => $credentials->reminder_template,
        ];
    }

    private function maskSecret(?string $value): ?string {
        if ($value === null || $value === '') {
            return null;
        }

        if (strlen($value) <= 4) {
            return str_repeat('•', strlen($value));
        }

        return str_repeat('•', 8).substr($value, -4);
    }
}

