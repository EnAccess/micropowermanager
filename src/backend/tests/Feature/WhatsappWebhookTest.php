<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Plugins\WhatsappBusinessApi\Models\WhatsappCredential;
use Illuminate\Support\Facades\Crypt;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class WhatsappWebhookTest extends TestCase {
    use RefreshMultipleDatabases;

    private string $webhookUrl;
    private string $webhookSecret = 'test-whatsapp-secret-token';

    protected function setUp(): void {
        parent::setUp();
        $this->webhookUrl = '/api/whatsapp-business-api/webhook/'.$this->companyId;
    }

    private function createCredentialWithSecret(string $secret): void {
        WhatsappCredential::query()->updateOrCreate(['id' => 1], [
            'access_token' => Crypt::encryptString('test-access-token'),
            'phone_number_id' => Crypt::encryptString('123456789'),
            'waba_id' => Crypt::encryptString('987654321'),
            'webhook_secret' => Crypt::encryptString($secret),
        ]);
    }

    public function testWebhookVerificationSucceedsWithValidToken(): void {
        $this->createCredentialWithSecret($this->webhookSecret);

        $challenge = '1158201444';
        $response = $this->get($this->webhookUrl.'?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => $this->webhookSecret,
            'hub_challenge' => $challenge,
        ]));

        $response->assertStatus(200);
        $this->assertSame($challenge, $response->getContent());
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function testWebhookVerificationFailsWithInvalidToken(): void {
        $this->createCredentialWithSecret($this->webhookSecret);

        $response = $this->get($this->webhookUrl.'?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'wrong-verify-token',
            'hub_challenge' => '1158201444',
        ]));

        $response->assertStatus(403);
        $this->assertSame('Forbidden', $response->getContent());
    }

    public function testWebhookVerificationFailsWithInvalidMode(): void {
        $this->createCredentialWithSecret($this->webhookSecret);

        $response = $this->get($this->webhookUrl.'?'.http_build_query([
            'hub_mode' => 'unsubscribe',
            'hub_verify_token' => $this->webhookSecret,
            'hub_challenge' => '1158201444',
        ]));

        $response->assertStatus(403);
    }

    public function testWebhookHandlesIncomingEventSuccessfully(): void {
        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '987654321',
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => '15550234567',
                                    'phone_number_id' => '123456789',
                                ],
                                'statuses' => [
                                    [
                                        'id' => 'wamid.HBgLMTIzNDU2',
                                        'status' => 'delivered',
                                        'timestamp' => '1728500000',
                                        'recipient_id' => '255712345678',
                                    ],
                                ],
                            ],
                            'field' => 'messages',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson($this->webhookUrl, $payload);

        $response->assertStatus(200);
        $this->assertSame('EVENT_RECEIVED', $response->getContent());
    }
}
