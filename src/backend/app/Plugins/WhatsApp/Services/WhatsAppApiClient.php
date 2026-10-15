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

        $rawError = (string) ($response->json('error.message') ?? $response->body());
        $errorCode = $response->json('error.code');

        // If the customer has not replied in 24 hours, automatically send the approved hello_world template
        // so the customer receives the message and opens the conversation window.
        if ($errorCode === 131047 || str_contains($rawError, '131047') || str_contains($rawError, 'Re-engagement')) {
            try {
                $templateResponse = Http::withToken($credentials->api_access_token)
                    ->acceptJson()
                    ->timeout(self::TIMEOUT_SECONDS)
                    ->post($endpoint, [
                        'messaging_product' => 'whatsapp',
                        'recipient_type' => 'individual',
                        'to' => $cleanPhone,
                        'type' => 'template',
                        'template' => [
                            'name' => 'hello_world',
                            'language' => ['code' => 'en_US'],
                        ],
                    ]);

                if ($templateResponse->successful()) {
                    return (string) $templateResponse->json('messages.0.id');
                }
            } catch (\Throwable) {
                // Fall through to humanized error
            }
        }

        Log::error('WhatsApp Cloud API send failed', [
            'status' => $response->status(),
            'error' => $rawError,
            'recipient' => $recipientPhone,
        ]);

        throw new WhatsAppApiException(self::humanizeError($rawError));
    }

    public static function humanizeError(string $rawError): string {
        if (str_contains($rawError, '131030') || str_contains($rawError, 'not in allowed list')) {
            return 'Recipient phone number is not registered in your Meta Developer Sandbox. In test mode, you must add this number to "Manage phone number list" in the Meta Developer Portal before sending messages to it.';
        }

        if (str_contains($rawError, '131047') || str_contains($rawError, '24 hours') || str_contains($rawError, 'Re-engagement')) {
            return 'Outside 24-hour service window. The customer has not replied in the last 24 hours. An approved template message must be used to open a conversation.';
        }

        if (str_contains($rawError, '190') || str_contains($rawError, 'Session has expired') || str_contains($rawError, 'access token')) {
            return 'Your Meta API access token has expired or is invalid. Please generate a new token in the Meta Developer Portal and save it in WhatsApp Settings.';
        }

        if (str_contains($rawError, '131000') || str_contains($rawError, 'Message failed to send')) {
            return 'Message delivery failed. Please verify that the recipient phone number is active on WhatsApp.';
        }

        if (str_contains($rawError, 'Invalid recipient phone')) {
            return 'Invalid phone number. Please enter a valid international phone number with country code.';
        }

        return $rawError;
    }
}
