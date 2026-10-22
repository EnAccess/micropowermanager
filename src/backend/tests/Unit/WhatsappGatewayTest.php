<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use App\Models\Device;
use App\Models\MainSettings;
use App\Models\Person\Person;
use App\Models\Sms;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use App\Plugins\WhatsappBusinessApi\Exceptions\MessageNotSentException;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappBusinessOptIn;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappCredential;
use App\Plugins\WhatsappBusinessApi\Services\WhatsappCredentialService;
use App\Plugins\WhatsappBusinessApi\WhatsappGateway;
use App\Services\MainSettingsService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class WhatsappGatewayTest extends TestCase {
    private WhatsappGateway $gateway;
    private WhatsappCredentialService&MockObject $credentialService;
    private MainSettingsService&MockObject $mainSettingsService;

    protected function setUp(): void {
        parent::setUp();

        $this->credentialService = $this->createMock(WhatsappCredentialService::class);
        $this->mainSettingsService = $this->createMock(MainSettingsService::class);

        $mainSettings = new MainSettings(['currency' => 'TZS']);
        $this->mainSettingsService->method('getAll')->willReturn(new Collection([$mainSettings]));

        $this->gateway = new WhatsappGateway(
            $this->credentialService,
            $this->mainSettingsService,
        );
    }

    public function testThrowsExceptionWhenNoSmsProvided(): void {
        $this->expectException(MessageNotSentException::class);
        $this->expectExceptionMessage('No SMS record provided');

        $this->gateway->sendSms('Test message', '+255712345678', null);
    }

    public function testThrowsExceptionWhenCredentialsNotConfigured(): void {
        $sms = new Sms(['receiver' => '+255712345678']);
        $this->credentialService->method('getCredentials')->willReturn(null);

        $this->expectException(MessageNotSentException::class);
        $this->expectExceptionMessage('WhatsApp Business API credentials not configured');

        $this->gateway->sendSms('Test message', '+255712345678', $sms);
    }

    public function testThrowsExceptionWhenPersonIdCannotBeResolved(): void {
        $credential = new WhatsappCredential([
            'access_token' => 'test-token',
            'phone_number_id' => '123456789',
        ]);
        $this->credentialService->method('getCredentials')->willReturn($credential);

        $sms = new Sms(['receiver' => '+255712345678']);
        // No trigger set

        $this->expectException(MessageNotSentException::class);
        $this->expectExceptionMessage('Could not resolve Person ID for opt-in check');

        $this->gateway->sendSms('Test message', '+255712345678', $sms);
    }

    public function testThrowsExceptionWhenCustomerHasNotOptedIn(): void {
        $credential = new WhatsappCredential([
            'access_token' => 'test-token',
            'phone_number_id' => '123456789',
        ]);
        $this->credentialService->method('getCredentials')->willReturn($credential);

        $person = new Person(['id' => 99]);
        $device = new Device();
        $device->person_id = 99;

        $transaction = new Transaction(['amount' => 5000]);
        $transaction->setRelation('device', $device);

        $sms = new Sms(['receiver' => '+255712345678']);
        $sms->setRelation('trigger', $transaction);

        // Ensure opt-in is false or does not exist for person 99
        WhatsappBusinessOptIn::query()->where('person_id', 99)->delete();

        $this->expectException(MessageNotSentException::class);
        $this->expectExceptionMessage('Customer has not opted in for WhatsApp messages');

        $this->gateway->sendSms('Test message', '+255712345678', $sms);
    }

    public function testSendsTransactionTemplateSuccessfullyWhenOptedIn(): void {
        Http::fake([
            'https://graph.facebook.com/v18.0/123456789/messages' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.HBgLMTIzNDU2']],
            ], 200),
        ]);

        $credential = new WhatsappCredential([
            'access_token' => 'meta-access-token',
            'phone_number_id' => '123456789',
            'transaction_confirmation_template' => 'transaction_receipt_v1',
        ]);
        $this->credentialService->method('getCredentials')->willReturn($credential);

        $person = Person::factory()->create();
        WhatsappBusinessOptIn::query()->updateOrCreate(
            ['person_id' => $person->id],
            ['opted_in' => true],
        );

        $device = new Device();
        $device->person_id = $person->id;

        $token = new Token(['token' => '1234-5678-9012']);

        $transaction = new Transaction(['amount' => 15000]);
        $transaction->setRelation('device', $device);
        $transaction->setRelation('token', $token);

        $sms = new Sms(['receiver' => '+255 712 345 678']);
        $sms->setRelation('trigger', $transaction);

        $this->gateway->sendSms('Test message', '+255 712 345 678', $sms);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://graph.facebook.com/v18.0/123456789/messages'
                && $request->hasHeader('Authorization', 'Bearer meta-access-token')
                && $request['messaging_product'] === 'whatsapp'
                && $request['to'] === '255712345678'
                && $request['type'] === 'template'
                && $request['template']['name'] === 'transaction_receipt_v1'
                && $request['template']['components'][0]['parameters'][0]['text'] === '15000 TZS'
                && $request['template']['components'][0]['parameters'][1]['text'] === '1234-5678-9012';
        });
    }

    public function testSendsApplianceReminderTemplateSuccessfullyWhenOptedIn(): void {
        Http::fake([
            'https://graph.facebook.com/v18.0/123456789/messages' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.HBgLMjM0NTY3']],
            ], 200),
        ]);

        $credential = new WhatsappCredential([
            'access_token' => 'meta-access-token',
            'phone_number_id' => '123456789',
            'reminder_template' => 'payment_reminder_v1',
        ]);
        $this->credentialService->method('getCredentials')->willReturn($credential);

        $person = Person::factory()->create();
        WhatsappBusinessOptIn::query()->updateOrCreate(
            ['person_id' => $person->id],
            ['opted_in' => true],
        );

        $appliancePerson = new AppliancePerson();
        $appliancePerson->person_id = $person->id;

        $rate = new ApplianceRate([
            'remaining' => 8000,
            'due_date' => '2026-10-15',
        ]);
        $rate->setRelation('appliancePerson', $appliancePerson);

        $sms = new Sms(['receiver' => '+255712345678']);
        $sms->setRelation('trigger', $rate);

        $this->gateway->sendSms('Test message', '+255712345678', $sms);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://graph.facebook.com/v18.0/123456789/messages'
                && $request['template']['name'] === 'payment_reminder_v1'
                && $request['template']['components'][0]['parameters'][0]['text'] === '8000 TZS'
                && $request['template']['components'][0]['parameters'][1]['text'] === '2026-10-15';
        });
    }

    public function testThrowsExceptionWhenTemplateNotConfigured(): void {
        $credential = new WhatsappCredential([
            'access_token' => 'meta-access-token',
            'phone_number_id' => '123456789',
            'transaction_confirmation_template' => null,
        ]);
        $this->credentialService->method('getCredentials')->willReturn($credential);

        $person = Person::factory()->create();
        WhatsappBusinessOptIn::query()->updateOrCreate(
            ['person_id' => $person->id],
            ['opted_in' => true],
        );

        $device = new Device();
        $device->person_id = $person->id;

        $transaction = new Transaction(['amount' => 1000]);
        $transaction->setRelation('device', $device);

        $sms = new Sms(['receiver' => '+255712345678']);
        $sms->setRelation('trigger', $transaction);

        $this->expectException(MessageNotSentException::class);
        $this->expectExceptionMessage('Transaction confirmation template not configured');

        $this->gateway->sendSms('Test message', '+255712345678', $sms);
    }

    public function testThrowsExceptionWhenGraphApiReturnsError(): void {
        Http::fake([
            'https://graph.facebook.com/v18.0/123456789/messages' => Http::response([
                'error' => [
                    'message' => 'Rate limit exceeded',
                    'type' => 'OAuthException',
                    'code' => 80007,
                ],
            ], 429),
        ]);

        $credential = new WhatsappCredential([
            'access_token' => 'meta-access-token',
            'phone_number_id' => '123456789',
            'transaction_confirmation_template' => 'transaction_receipt_v1',
        ]);
        $this->credentialService->method('getCredentials')->willReturn($credential);

        $person = Person::factory()->create();
        WhatsappBusinessOptIn::query()->updateOrCreate(
            ['person_id' => $person->id],
            ['opted_in' => true],
        );

        $device = new Device();
        $device->person_id = $person->id;

        $transaction = new Transaction(['amount' => 5000]);
        $transaction->setRelation('device', $device);

        $sms = new Sms(['receiver' => '+255712345678']);
        $sms->setRelation('trigger', $transaction);

        $this->expectException(MessageNotSentException::class);
        $this->expectExceptionMessage('WhatsApp message sending failed');

        $this->gateway->sendSms('Test message', '+255712345678', $sms);
    }

    public function testSendInteractiveMenuDispatchesCorrectPayload(): void {
        Http::fake([
            'https://graph.facebook.com/v18.0/123456789/messages' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.INTERACTIVE_123']],
            ], 200),
        ]);

        $credential = new WhatsappCredential([
            'access_token' => 'meta-access-token',
            'phone_number_id' => '123456789',
        ]);
        $this->credentialService->method('getCredentials')->willReturn($credential);

        $options = [
            ['id' => 'btn_latest_token', 'title' => 'Get latest token', 'description' => 'Latest token'],
            ['id' => 'btn_not_received_token', 'title' => 'Did not receive token'],
        ];

        $messageId = $this->gateway->sendInteractiveMenu(
            receiver: '+255712345678',
            bodyText: 'Please select an option',
            options: $options,
            buttonText: 'Select Option',
            headerText: 'Menu Header',
            footerText: 'MPM Footer',
        );

        $this->assertSame('wamid.INTERACTIVE_123', $messageId);

        Http::assertSent(function (Request $request): bool {
            $data = $request->data();

            return $request->url() === 'https://graph.facebook.com/v18.0/123456789/messages'
                && ($data['to'] ?? '') === '255712345678'
                && ($data['type'] ?? '') === 'interactive'
                && ($data['interactive']['type'] ?? '') === 'list'
                && ($data['interactive']['body']['text'] ?? '') === 'Please select an option'
                && ($data['interactive']['action']['button'] ?? '') === 'Select Option'
                && ($data['interactive']['header']['text'] ?? '') === 'Menu Header'
                && ($data['interactive']['footer']['text'] ?? '') === 'MPM Footer'
                && count($data['interactive']['action']['sections'][0]['rows'] ?? []) === 2;
        });
    }

    public function testSendTextMessageDispatchesCorrectPayload(): void {
        Http::fake([
            'https://graph.facebook.com/v18.0/123456789/messages' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.TEXT_999']],
            ], 200),
        ]);

        $credential = new WhatsappCredential([
            'access_token' => 'meta-access-token',
            'phone_number_id' => '123456789',
        ]);
        $this->credentialService->method('getCredentials')->willReturn($credential);

        $messageId = $this->gateway->sendTextMessage('+255712345678', 'Hello from MPM support');

        $this->assertSame('wamid.TEXT_999', $messageId);

        Http::assertSent(function (Request $request): bool {
            $data = $request->data();

            return $request->url() === 'https://graph.facebook.com/v18.0/123456789/messages'
                && ($data['to'] ?? '') === '255712345678'
                && ($data['type'] ?? '') === 'text'
                && ($data['text']['body'] ?? '') === 'Hello from MPM support';
        });
    }
}
