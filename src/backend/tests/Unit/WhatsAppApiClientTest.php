<?php

namespace Tests\Unit;

use App\Plugins\WhatsApp\Exceptions\WhatsAppApiException;
use App\Plugins\WhatsApp\Models\WhatsAppCredential;
use App\Plugins\WhatsApp\Models\WhatsAppMessage;
use App\Plugins\WhatsApp\Services\WhatsAppApiClient;
use App\Plugins\WhatsApp\Services\WhatsAppCredentialService;
use Illuminate\Support\Facades\Http;
use Tests\WhatsAppTestCase;

class WhatsAppApiClientTest extends WhatsAppTestCase {
    private WhatsAppApiClient $client;
    private WhatsAppCredentialService $credentialService;

    protected function setUp(): void {
        parent::setUp();

        $this->credentialService = resolve(WhatsAppCredentialService::class);
        $this->client = resolve(WhatsAppApiClient::class);
    }

    private function configureCredentials(): WhatsAppCredential {
        return $this->credentialService->updateCredentials([
            'phone_number_id' => '1098234982374982',
            'whatsapp_business_account_id' => '987234982374982',
            'api_access_token' => 'test-valid-access-token',
            'sender_phone_number' => '+250788123456',
            'api_version' => 'v21.0',
            'is_enabled' => true,
        ]);
    }

    public function testSendTextMessageThrowsWhenNotConfigured(): void {
        $this->expectException(WhatsAppApiException::class);
        $this->expectExceptionMessage('WhatsApp API credentials are not configured or missing.');

        $this->client->sendTextMessage('+250788123456', 'Test message');
    }

    public function testSendTextMessageThrowsForInvalidPhone(): void {
        $this->configureCredentials();

        $this->expectException(WhatsAppApiException::class);
        $this->expectExceptionMessage('Invalid recipient phone number.');

        $this->client->sendTextMessage('invalid-phone', 'Test message');
    }

    public function testSendTextMessageSuccessLogsSentMessage(): void {
        $this->configureCredentials();

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '250788123456', 'wa_id' => '250788123456']],
                'messages' => [['id' => 'wamid.HBgLMjUwNzg4MTIzNDU2FQIAERgSQ0IxOU...']],
            ], 200),
        ]);

        $message = $this->client->sendTextMessage('+250788123456', 'Hello from test!');

        $this->assertInstanceOf(WhatsAppMessage::class, $message);
        $this->assertSame('sent', $message->status);
        $this->assertSame('wamid.HBgLMjUwNzg4MTIzNDU2FQIAERgSQ0IxOU...', $message->message_id);
        $this->assertSame('+250788123456', $message->recipient_phone);
        $this->assertSame('Hello from test!', $message->body);
        $this->assertNotNull($message->sent_at);
    }

    public function testSendTextMessageFailureLogsFailedMessageAndThrows(): void {
        $this->configureCredentials();

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Malformed access token',
                    'type' => 'OAuthException',
                    'code' => 190,
                ],
            ], 400),
        ]);

        try {
            $this->client->sendTextMessage('+250788123456', 'Hello from test!');
            $this->fail('Expected WhatsAppApiException was not thrown.');
        } catch (WhatsAppApiException $e) {
            $this->assertStringContainsString('Malformed access token', $e->getMessage());
        }

        $loggedMessage = WhatsAppMessage::query()->latest()->first();
        $this->assertNotNull($loggedMessage);
        $this->assertSame('failed', $loggedMessage->status);
        $this->assertSame('Malformed access token', $loggedMessage->error_message);
    }

    public function testSendTestMessageUsesTestNotificationType(): void {
        $this->configureCredentials();

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '250788123456', 'wa_id' => '250788123456']],
                'messages' => [['id' => 'wamid.test_id_999']],
            ], 200),
        ]);

        $message = $this->client->sendTestMessage('+250788123456');

        $this->assertSame('test', $message->notification_type);
        $this->assertSame('sent', $message->status);
    }
}
