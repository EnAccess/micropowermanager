<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MpmPlugin;
use App\Models\Sms;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappBusinessOptIn;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappBusinessOptInHistory;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappCredential;
use App\Plugins\WhatsappBusinessApi\WhatsappGateway;
use Database\Factories\Address\AddressFactory;
use Database\Factories\Person\PersonFactory;
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

    private function signPayload(array $payload, ?string $secret = null): string {
        $secretKey = $secret ?? $this->webhookSecret;

        return hash_hmac('sha256', json_encode($payload), $secretKey);
    }

    private function getSamplePayload(): array {
        return [
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
    }

    public function testWebhookHandlesIncomingEventSuccessfully(): void {
        $payload = $this->getSamplePayload();

        $response = $this->postJson($this->webhookUrl, $payload);

        $response->assertStatus(200);
        $this->assertSame('EVENT_RECEIVED', $response->getContent());
    }

    public function testWebhookHandlesIncomingEventWithValidSignatureHeader(): void {
        $this->createCredentialWithSecret($this->webhookSecret);

        $payload = $this->getSamplePayload();
        $signature = $this->signPayload($payload);

        $response = $this->postJson($this->webhookUrl, $payload, [
            'X-Hub-Signature-256' => 'sha256='.$signature,
        ]);

        $response->assertStatus(200);
        $this->assertSame('EVENT_RECEIVED', $response->getContent());
    }

    public function testWebhookHandlesIncomingEventWithValidSignatureWithoutPrefix(): void {
        $this->createCredentialWithSecret($this->webhookSecret);

        $payload = $this->getSamplePayload();
        $signature = $this->signPayload($payload);

        $response = $this->postJson($this->webhookUrl, $payload, [
            'X-Hub-Signature-256' => $signature,
        ]);

        $response->assertStatus(200);
        $this->assertSame('EVENT_RECEIVED', $response->getContent());
    }

    public function testWebhookRejectsIncomingEventWithInvalidSignature(): void {
        $this->createCredentialWithSecret($this->webhookSecret);

        $payload = $this->getSamplePayload();

        $response = $this->postJson($this->webhookUrl, $payload, [
            'X-Hub-Signature-256' => 'sha256=invalid_hash_that_does_not_match',
        ]);

        $response->assertStatus(401);
        $this->assertSame('Unauthorized: Invalid or missing X-Hub-Signature-256', $response->getContent());
    }

    public function testWebhookRejectsTamperedPayloadWithValidSignatureHeader(): void {
        $this->createCredentialWithSecret($this->webhookSecret);

        $payload = $this->getSamplePayload();
        $signature = $this->signPayload($payload);

        // Tamper with payload content after signing
        $payload['entry'][0]['id'] = 'tampered_malicious_id';

        $response = $this->postJson($this->webhookUrl, $payload, [
            'X-Hub-Signature-256' => 'sha256='.$signature,
        ]);

        $response->assertStatus(401);
        $this->assertSame('Unauthorized: Invalid or missing X-Hub-Signature-256', $response->getContent());
    }

    public function testWebhookRejectsMissingSignatureWhenEnforced(): void {
        $this->createCredentialWithSecret($this->webhookSecret);

        $payload = $this->getSamplePayload();

        $response = $this->postJson($this->webhookUrl, $payload, [
            'X-Force-Signature-Check' => '1',
        ]);

        $response->assertStatus(401);
        $this->assertSame('Unauthorized: Invalid or missing X-Hub-Signature-256', $response->getContent());
    }

    public function testDeliveredStatusDoesNotOverwriteFallbackGatewayStatus(): void {
        $wamid = 'wamid.fallback123';
        $sms = Sms::query()->create([
            'receiver' => '+255712345678',
            'body' => 'Your electricity token is 1234-5678',
            'sender_id' => null,
            'direction' => Sms::DIRECTION_OUTGOING,
            'status' => Sms::STATUS_SENT,
            'gateway_id' => 1,
            'uuid' => $wamid,
        ]);

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
                                        'id' => $wamid,
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

        $sms->refresh();
        $this->assertSame(1, $sms->gateway_id);
        $this->assertSame(Sms::STATUS_SENT, $sms->status);
    }

    public function testOutOfOrderFailedStatusIgnoredIfAlreadyDelivered(): void {
        $wamid = 'wamid.alreadyDelivered123';
        $sms = Sms::query()->create([
            'receiver' => '+255712345678',
            'body' => 'Your electricity token is 1234-5678',
            'sender_id' => null,
            'direction' => Sms::DIRECTION_OUTGOING,
            'status' => Sms::STATUS_DELIVERED,
            'gateway_id' => MpmPlugin::WHATSAPP_BUSINESS_API,
            'uuid' => $wamid,
        ]);

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
                                        'id' => $wamid,
                                        'status' => 'failed',
                                        'timestamp' => '1728500100',
                                        'recipient_id' => '255712345678',
                                        'errors' => [
                                            ['message' => 'Delivery timeout'],
                                        ],
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

        $sms->refresh();
        $this->assertSame(Sms::STATUS_DELIVERED, $sms->status);
        $this->assertSame(MpmPlugin::WHATSAPP_BUSINESS_API, $sms->gateway_id);
    }

    public function testInboundStartAndStopKeywordsRecordOptInHistory(): void {
        $this->createCredentialWithSecret($this->webhookSecret);

        $mockGateway = $this->createMock(WhatsappGateway::class);
        $mockGateway->method('sendTextMessage')->willReturn('wamid.reply123');
        $this->instance(WhatsappGateway::class, $mockGateway);

        $person = PersonFactory::new()->create();
        $phoneNumber = '+255712999888';
        AddressFactory::new()->create([
            'owner_id' => $person->id,
            'owner_type' => 'person',
            'phone' => $phoneNumber,
        ]);

        // 1. Send STOP command
        $stopPayload = [
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
                                'messages' => [
                                    [
                                        'from' => '255712999888',
                                        'id' => 'wamid.inboundStop',
                                        'timestamp' => '1728500200',
                                        'type' => 'text',
                                        'text' => [
                                            'body' => 'STOP',
                                        ],
                                    ],
                                ],
                            ],
                            'field' => 'messages',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson($this->webhookUrl, $stopPayload);
        $response->assertStatus(200);

        $this->assertDatabaseHas('whatsapp_business_opt_ins', [
            'person_id' => $person->id,
            'opted_in' => 0,
        ], 'tenant');

        $this->assertDatabaseHas('whatsapp_business_opt_in_histories', [
            'person_id' => $person->id,
            'opted_in' => 0,
            'actor_type' => 'customer',
            'actor_id' => $person->id,
            'source' => WhatsappBusinessOptInHistory::SOURCE_WEBHOOK_COMMAND,
        ], 'tenant');

        // 2. Send START command
        $startPayload = [
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
                                'messages' => [
                                    [
                                        'from' => '255712999888',
                                        'id' => 'wamid.inboundStart',
                                        'timestamp' => '1728500300',
                                        'type' => 'text',
                                        'text' => [
                                            'body' => 'START',
                                        ],
                                    ],
                                ],
                            ],
                            'field' => 'messages',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson($this->webhookUrl, $startPayload);
        $response->assertStatus(200);

        $this->assertDatabaseHas('whatsapp_business_opt_ins', [
            'person_id' => $person->id,
            'opted_in' => 1,
        ], 'tenant');

        $this->assertDatabaseHas('whatsapp_business_opt_in_histories', [
            'person_id' => $person->id,
            'opted_in' => 1,
            'actor_type' => 'customer',
            'actor_id' => $person->id,
            'source' => WhatsappBusinessOptInHistory::SOURCE_WEBHOOK_COMMAND,
        ], 'tenant');
    }
}
