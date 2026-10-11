<?php

namespace App\Plugins\WhatsApp\Services;

use App\Plugins\WhatsApp\Exceptions\WhatsAppApiException;
use App\Plugins\WhatsApp\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppApiClient {
    private const string BASE_GRAPH_URL = 'https://graph.facebook.com';

    public function __construct(
        private WhatsAppCredentialService $credentialService,
        private WhatsAppMessage $messageModel,
    ) {}

    /**
     * send a plain text message via Meta WhatsApp Cloud API.
     * 
     * @throws WhatsAppApiException
     */
    public function sendTextMessage(
        string $recipientPhone,
        string $messageText,
        string $notificationType = 'custom',
    ): WhatsAppMessage {
        $credentials = $this->credentialService->getCredentials();

        if(!$credentials || empty($credentials->phone_number_id) || empty($credentials->api_access_token)) {
            throw new WhatsAppApiException('WhatsApp API credentials are not configured or missing.');
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $recipientPhone);
        if(empty($cleanPhone)) {
            throw new WhatsAppApiException('Invalid recipient phone number.');
        }

        $apiVersion = $credentials->api_version ?: 'v21.0';
        $endpoint = self::BASE_GRAPH_URL . "/{$apiVersion}/{$credentials->phone_number_id}/messages";

        try{
            $response = Http::withToken($credentials->api_access_token)
                ->acceptJson()
                ->timeout(15)
                ->post($endpoint, [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $cleanPhone,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $messageText
                    ],
                ]);

            if ($response->successful()) {
                $messageId = $response->json('messages.0.id');

                return $this->messageModel->newQuery()->create([
                    'message_id' => $messageId,
                    'recipient_phone' => $recipientPhone,
                    'notification_type' => $notificationType,
                    'status' => 'sent',
                    'body' => $messageText,
                    'sent_at' => now(),
                ]);
            }

            $errorMessage = $response->json('error.message') ?? $response->body();
            Log::error('WhatsApp Cloud API send failed', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'recipient' => $recipientPhone,
            ]);

            $this->messageModel->newQuery()->create([
                'recipient_phone' => $recipientPhone,
                'notification_type' => $notificationType,
                'status' => 'failed',
                'body' => $messageText,
                'error_message' => $errorMessage,
            ]);

            throw new WhatsAppApiException('WhatsApp API error:' . $errorMessage);
        } catch (WhatsAppApiException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('WhatsApp Cloud API connection exception', [
                'error' => $e->getMessage(),
                'recipient' => $recipientPhone,
            ]);

            $this->messageModel->newQuery()->create([
                'recipient_phone' => $recipientPhone,
                'notification_type' => $notificationType,
                'status' => 'failed',
                'body' => $messageText,
                'error_message' => $e->getMessage(),
            ]);

            throw new WhatsAppApiException('Failed to connect to WhatsApp API: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Send a test message to verify the WhatsApp integration
     * 
     * @throws WhatsAppApiException
     */
    public function sendTestMessage(string $recipientPhone, ?string $customText = null): WhatsAppMessage {
        $text = $customText ?: 'Hello from PowerEcho! This is a test notification from your MicroPowerManager WhatsApp integration.';

        return $this->sendTextMessage($recipientPhone, $text, 'test');
    }
}
