<?php

namespace App\Plugins\WhatsApp\Services;

use App\Plugins\WhatsApp\Exceptions\WhatsAppApiException;
use App\Plugins\WhatsApp\Models\WhatsAppCredential;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppApiClient {
    private const string BASE_GRAPH_URL = 'https://graph.facebook.com';
    private const int TIMEOUT_SECONDS = 15;

    public function __construct(
        private WhatsAppCredentialService $credentialService,
    ) {}

    /**
     * Send a plain text message and return the provider message id.
     *
     * Persistence is owned by WhatsAppMessageService so the message lifecycle
     * (queued -> sending -> sent/failed) is recorded around the API call.
     *
     * @throws WhatsAppApiException
     */
    public function sendTextMessage(string $recipientPhone, string $messageText): string {
        $credentials = $this->credentialService->getCredentials();

        if (!$credentials instanceof WhatsAppCredential || empty($credentials->phone_number_id) || empty($credentials->api_access_token)) {
            throw new WhatsAppApiException('WhatsApp API credentials are not configured or missing.');
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $recipientPhone);

        if (empty($cleanPhone)) {
            throw new WhatsAppApiException('Invalid recipient phone number.');
        }

        $apiVersion = $credentials->api_version ?: 'v21.0';
        $endpoint = self::BASE_GRAPH_URL."/{$apiVersion}/{$credentials->phone_number_id}/messages";

        try {
            $response = Http::withToken($credentials->api_access_token)
                ->acceptJson()
                ->timeout(self::TIMEOUT_SECONDS)
                ->post($endpoint, [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $cleanPhone,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $messageText,
                    ],
                ]);
        } catch (\Throwable $exception) {
            Log::error('WhatsApp Cloud API connection exception', [
                'error' => $exception->getMessage(),
                'recipient' => $recipientPhone,
            ]);

            throw new WhatsAppApiException('Failed to connect to WhatsApp API: '.$exception->getMessage(), 0, $exception);
        }

        if ($response->successful()) {
            return (string) $response->json('messages.0.id');
        }

        $errorMessage = $response->json('error.message') ?? $response->body();
        Log::error('WhatsApp Cloud API send failed', [
            'status' => $response->status(),
            'error' => $errorMessage,
            'recipient' => $recipientPhone,
        ]);

        throw new WhatsAppApiException('WhatsApp API error: '.$errorMessage);
    }
}
