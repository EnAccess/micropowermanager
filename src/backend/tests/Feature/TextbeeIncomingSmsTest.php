<?php

namespace Tests\Feature;

use App\Events\SmsStoredEvent;
use App\Models\Address\Address;
use App\Models\Sms;
use App\Plugins\TextbeeSmsGateway\Models\TextbeeCredential;
use Database\Factories\Address\AddressFactory;
use Database\Factories\Person\PersonFactory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class TextbeeIncomingSmsTest extends TestCase {
    use RefreshMultipleDatabases;

    private string $webhookUrl;
    private string $webhookSecret = 'test-webhook-secret';

    protected function setUp(): void {
        parent::setUp();
        $this->webhookUrl = '/api/textbee-sms-gateway/callback/'.$this->companyId.'/incoming-messages';
    }

    private function createCredentialWithSecret(): void {
        TextbeeCredential::query()->create([
            'api_key' => Crypt::encryptString('test-api-key'),
            'device_id' => Crypt::encryptString('test-device-id'),
            'webhook_secret' => Crypt::encryptString($this->webhookSecret),
        ]);
    }

    private function createCredentialWithoutSecret(): void {
        TextbeeCredential::query()->create([
            'api_key' => Crypt::encryptString('test-api-key'),
            'device_id' => Crypt::encryptString('test-device-id'),
            'webhook_secret' => null,
        ]);
    }

    private function createCustomerAddress(string $phone): Address {
        $person = PersonFactory::new()->create();
        $address = AddressFactory::new()->make(['phone' => $phone]);
        $address->owner()->associate($person);
        $address->save();

        return $address;
    }

    private function buildPayload(string $sender = '+255712345678', string $message = 'Hello from SMS'): array {
        return [
            'smsId' => 'sms-123',
            'sender' => $sender,
            'message' => $message,
            'receivedAt' => '2025-10-05T13:00:35.208Z',
            'deviceId' => 'device-123',
            'webhookSubscriptionId' => 'sub-123',
            'webhookEvent' => 'MESSAGE_RECEIVED',
        ];
    }

    private function signPayload(array $payload): string {
        return hash_hmac('sha256', json_encode($payload), $this->webhookSecret);
    }

    public function testValidWebhookCreatesIncomingSmsRecord(): void {
        $this->createCredentialWithSecret();
        $this->createCustomerAddress('+255712345678');
        Event::fake([SmsStoredEvent::class]);

        $payload = $this->buildPayload();
        $signature = $this->signPayload($payload);

        $response = $this->postJson($this->webhookUrl, $payload, [
            'x-signature' => $signature,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('sms', [
            'receiver' => '+255712345678',
            'body' => 'Hello from SMS',
            'direction' => Sms::DIRECTION_INCOMING,
            'status' => Sms::STATUS_DELIVERED,
        ], 'tenant');
    }

    public function testInvalidSignatureReturns401(): void {
        $this->createCredentialWithSecret();

        $payload = $this->buildPayload();

        $response = $this->postJson($this->webhookUrl, $payload, [
            'x-signature' => 'invalid-signature',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['status' => 'unauthorized']);
    }

    public function testSmsStoredEventIsDispatched(): void {
        $this->createCredentialWithSecret();
        $this->createCustomerAddress('+255787654321');
        Event::fake([SmsStoredEvent::class]);

        $payload = $this->buildPayload('+255787654321', 'Test message');
        $signature = $this->signPayload($payload);

        $response = $this->postJson($this->webhookUrl, $payload, [
            'x-signature' => $signature,
        ]);

        $response->assertStatus(200);

        Event::assertDispatched(SmsStoredEvent::class, fn (SmsStoredEvent $event): bool => $event->sender === '+255787654321'
            && $event->message === 'Test message'
            && $event->sms instanceof Sms);
    }

    public function testWebhookWorksWithoutSecret(): void {
        $this->createCredentialWithoutSecret();
        Event::fake([SmsStoredEvent::class]);

        $payload = $this->buildPayload();

        $response = $this->postJson($this->webhookUrl, $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
    }
}
