<?php

namespace Tests\Feature;

use App\Events\SmsStoredEvent;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

class WhatsappWebhookTest extends TestCase {
    private const APP_SECRET = 'test-app-secret';
    private const VERIFY_TOKEN = 'test-verify-token';
    private const PHONE_NUMBER_ID = '106540352242922';

    private string $webhookUrl;

    protected function setUp(): void {
        parent::setUp();
        $this->webhookUrl = '/api/whatsapp-messaging/webhook/'.$this->companyId;

        WhatsappCredential::query()->create([
            'access_token' => Crypt::encryptString('test-access-token'),
            'app_secret' => Crypt::encryptString(self::APP_SECRET),
            'webhook_verify_token' => Crypt::encryptString(self::VERIFY_TOKEN),
            'phone_number_id' => self::PHONE_NUMBER_ID,
            'business_account_id' => '123456789',
            'graph_api_version' => 'v26.0',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function statusPayload(string $phoneNumberId = self::PHONE_NUMBER_ID): array {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => '123456789',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => ['display_phone_number' => '15550783881', 'phone_number_id' => $phoneNumberId],
                        'statuses' => [[
                            'id' => 'wamid.xxx',
                            'status' => 'delivered',
                            'timestamp' => '1750263773',
                            'recipient_id' => '16505551234',
                            'pricing' => ['billable' => true, 'category' => 'utility'],
                            'errors' => [],
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function inboundMessagePayload(): array {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => '123456789',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => ['display_phone_number' => '15550783881', 'phone_number_id' => self::PHONE_NUMBER_ID],
                        'contacts' => [['profile' => ['name' => 'Jane Customer'], 'wa_id' => '16505551234']],
                        'messages' => [[
                            'from' => '16505551234',
                            'id' => 'wamid.inbound',
                            'timestamp' => '1750263773',
                            'type' => 'text',
                            'text' => ['body' => 'Paid 5000 TZS ref ABC123'],
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function postSigned(array $payload, ?string $signature = null): TestResponse {
        $body = json_encode($payload);
        $signature ??= 'sha256='.hash_hmac('sha256', $body, self::APP_SECRET);

        return $this->call('POST', $this->webhookUrl, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $body);
    }

    public function testVerifySucceedsWithCorrectToken(): void {
        $response = $this->get($this->webhookUrl.'?hub.mode=subscribe&hub.verify_token='.self::VERIFY_TOKEN.'&hub.challenge=1158201444');

        $response->assertOk();
        $this->assertSame('1158201444', $response->getContent());
        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
    }

    public function testVerifyRejectsWrongToken(): void {
        $response = $this->get($this->webhookUrl.'?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=1158201444');

        $response->assertForbidden();
        $this->assertStringNotContainsString('1158201444', $response->getContent());
    }

    public function testVerifyRejectsWrongMode(): void {
        $response = $this->get($this->webhookUrl.'?hub.mode=unsubscribe&hub.verify_token='.self::VERIFY_TOKEN.'&hub.challenge=1158201444');

        $response->assertForbidden();
    }

    public function testReceiveAcceptsValidSignature(): void {
        Log::spy();

        $response = $this->postSigned($this->statusPayload());

        $response->assertOk();
        $response->assertExactJson(['status' => 'ok']);
        Log::shouldHaveReceived('info')->with('WhatsApp status received', Mockery::type('array'))->once();
    }

    public function testReceiveRejectsInvalidSignature(): void {
        $response = $this->postSigned($this->statusPayload(), 'sha256='.str_repeat('0', 64));

        $response->assertForbidden();
    }

    public function testReceiveRejectsMissingSignature(): void {
        $response = $this->postJson($this->webhookUrl, $this->statusPayload());

        $response->assertForbidden();
    }

    public function testReceiveRejectsSignatureOverReEncodedBody(): void {
        $payload = $this->statusPayload();
        $body = json_encode($payload, JSON_PRETTY_PRINT);
        $signatureOfDifferentBytes = 'sha256='.hash_hmac('sha256', json_encode($payload), self::APP_SECRET);

        $response = $this->call('POST', $this->webhookUrl, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signatureOfDifferentBytes,
        ], $body);

        $response->assertForbidden();
    }

    public function testReceiveIgnoresUnknownPhoneNumberId(): void {
        Log::spy();

        $response = $this->postSigned($this->statusPayload('999999999999999'));

        $response->assertOk();
        Log::shouldNotHaveReceived('info', ['WhatsApp status received', Mockery::any()]);
    }

    public function testInboundMessageDoesNotFireSmsStoredEvent(): void {
        Event::fake([SmsStoredEvent::class]);
        Log::spy();

        $response = $this->postSigned($this->inboundMessagePayload());

        $response->assertOk();
        Event::assertNotDispatched(SmsStoredEvent::class);
        Log::shouldHaveReceived('info')->withArgs(
            fn (string $message, array $context): bool => $message === 'WhatsApp message received'
                && $context['contact_name'] === 'Jane Customer'
        )->once();
    }

    public function testWebhookResolvesCompanyFromPath(): void {
        $resolvedCompanyId = null;
        Route::matched(function ($event) use (&$resolvedCompanyId): void {
            $resolvedCompanyId = $event->request->attributes->get('companyId');
        });

        $this->postSigned($this->statusPayload())->assertOk();

        $this->assertSame($this->companyId, $resolvedCompanyId);
    }
}
