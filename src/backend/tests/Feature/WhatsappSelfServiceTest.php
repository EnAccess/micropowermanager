<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\MainSettings;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Plugins\WhatsappMessaging\Services\WhatsappWebhookService;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceRateFactory;
use Database\Factories\DeviceFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\TokenFactory;
use Database\Factories\TransactionFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class WhatsappSelfServiceTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string PHONE = '+255712345678';
    private const string WA_ID = '255712345678';
    private const string PHONE_NUMBER_ID = '1234567890';
    private const string MESSAGES_URL = 'https://graph.facebook.com/v26.0/'.self::PHONE_NUMBER_ID.'/messages';

    private WhatsappWebhookService $service;
    private WhatsappCredential $credential;

    protected function setUp(): void {
        parent::setUp();

        $this->credential = WhatsappCredential::query()->create([
            'access_token' => 'test-access-token',
            'app_secret' => 'test-app-secret',
            'webhook_verify_token' => 'test-verify-token',
            'phone_number_id' => self::PHONE_NUMBER_ID,
            'graph_api_version' => 'v26.0',
        ]);
        MainSettings::query()->firstOrFail()->update(['currency' => 'RWF', 'company_name' => 'Demo Power']);
        Http::fake([self::MESSAGES_URL => Http::response(['messages' => [['id' => 'wamid.reply']]])]);

        $this->service = resolve(WhatsappWebhookService::class);
    }

    public function testTokenRepliesWithTheLastTokenOfTheOnlyDevice(): void {
        $person = $this->createOptedInCustomer();
        $device = $this->createDevice($person, 'METER-001');
        $this->createPaymentWithToken($device, 500, '111122223333');
        $this->createPaymentWithToken($device, 1000, '743296403099');

        $this->receive($this->textMessage('TOKEN'));

        $this->assertReplied('Last payment for METER-001: 1000 RWF');
        $this->assertReplied('Token: 743296403099 for 6.667 kWh');
    }

    public function testTokenIsCaseInsensitiveAndIgnoresSurroundingSpaces(): void {
        $person = $this->createOptedInCustomer();
        $this->createPaymentWithToken($this->createDevice($person, 'METER-001'), 1000, '743296403099');

        $this->receive($this->textMessage('  token  '));

        $this->assertReplied('Token: 743296403099');
    }

    public function testTokenWithSerialOfAnotherCustomerLooksLikeAnUnknownSerial(): void {
        $this->createDevice($this->createOptedInCustomer(), 'METER-001');
        $otherCustomer = PersonFactory::new()->create();
        $this->createPaymentWithToken($this->createDevice($otherCustomer, 'METER-999'), 1000, '999999999999');

        $this->receive($this->textMessage('TOKEN METER-999'));
        $this->receive($this->textMessage('TOKEN UNKNOWN-1'));

        $this->assertReplied('No device with serial METER-999 was found on your account.');
        $this->assertReplied('No device with serial UNKNOWN-1 was found on your account.');
        Http::assertNotSent(fn (Request $request): bool => str_contains((string) $request['text']['body'], '999999999999'));
    }

    public function testTokenWithSeveralDevicesListsThemAndTokenWithSerialAnswersForOne(): void {
        $person = $this->createOptedInCustomer();
        $this->createDevice($person, 'METER-001');
        $this->createPaymentWithToken($this->createDevice($person, 'METER-002'), 2000, '222233334444');

        $this->receive($this->textMessage('TOKEN'));
        $this->receive($this->textMessage('TOKEN METER-002'));

        $this->assertReplied("- METER-001\n- METER-002\nSend TOKEN <serial>");
        $this->assertReplied('Last payment for METER-002: 2000 RWF');
    }

    public function testBalanceSumsTheRemainingAmountsAndShowsTheNextDueDate(): void {
        $person = $this->createOptedInCustomer();
        $appliancePerson = AppliancePersonFactory::new()->create(['person_id' => $person->id, 'appliance_id' => 1]);
        ApplianceRateFactory::new()->create(['appliance_person_id' => $appliancePerson->id, 'remaining' => 3000, 'due_date' => '2026-11-15']);
        ApplianceRateFactory::new()->create(['appliance_person_id' => $appliancePerson->id, 'remaining' => 2000, 'due_date' => '2026-10-15']);
        ApplianceRateFactory::new()->create(['appliance_person_id' => $appliancePerson->id, 'remaining' => 0, 'due_date' => '2026-09-15']);

        $this->receive($this->textMessage('balance'));

        $this->assertReplied('Your outstanding appliance balance is 5000 RWF. Next payment: 2000 RWF due on 2026-10-15.');
    }

    public function testCustomerWhoIsNotOptedInGetsNoSelfServiceReply(): void {
        $person = PersonFactory::new()->create();
        WhatsappContact::query()->create([
            'phone' => self::PHONE,
            'person_id' => $person->id,
            'opt_in_status' => WhatsappContact::OPT_IN_STATUS_OPTED_OUT,
        ]);

        $this->receive($this->textMessage('TOKEN'));

        // A customer who is not subscribed is only offered the opt-in buttons, never a self-service answer.
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request['type'] === 'interactive');
    }

    public function testNumberNotLinkedToACustomerIsToldToContactTheCompany(): void {
        WhatsappContact::query()->create(['phone' => self::PHONE, 'opt_in_status' => WhatsappContact::OPT_IN_STATUS_OPTED_IN]);

        $this->receive($this->textMessage('TOKEN'));

        $this->assertReplied('This number is not linked to a customer account. Please contact Demo Power.');
    }

    public function testHelpAndUnknownTextListTheCommands(): void {
        $this->createOptedInCustomer();

        $this->receive($this->textMessage('HELP'));
        $this->receive($this->textMessage('Hello, my lights are off'));

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => str_contains((string) $request['text']['body'], 'BALANCE - your outstanding appliance balance')
            && str_contains((string) $request['text']['body'], 'Reply STOP to unsubscribe.'));
    }

    public function testResentWebhookIsAnsweredOnlyOnce(): void {
        $this->createOptedInCustomer();
        $message = $this->textMessage('HELP');

        $this->receive($message);
        $this->receive($message);

        Http::assertSentCount(1);
    }

    private function createOptedInCustomer(): Person {
        $person = PersonFactory::new()->create();
        WhatsappContact::query()->create([
            'phone' => self::PHONE,
            'wa_id' => self::WA_ID,
            'person_id' => $person->id,
            'opt_in_status' => WhatsappContact::OPT_IN_STATUS_OPTED_IN,
        ]);

        return $person;
    }

    private function createDevice(Person $person, string $serial): Device {
        return DeviceFactory::new()->create([
            'person_id' => $person->id,
            'device_type' => 'meter',
            'device_id' => 1,
            'device_serial' => $serial,
        ]);
    }

    private function createPaymentWithToken(Device $device, int $amount, string $token): Transaction {
        $transaction = TransactionFactory::new()->create([
            // The factory picks a random id; auto-increment keeps "latest payment" meaning what it means in production.
            'id' => null,
            'amount' => $amount,
            'message' => $device->device_serial,
            'original_transaction_id' => 1,
            'original_transaction_type' => 'cash_transaction',
        ]);
        TokenFactory::new()->create([
            'transaction_id' => $transaction->id,
            'device_id' => $device->id,
            'token' => $token,
            'token_amount' => 6.667,
        ]);

        return $transaction;
    }

    /**
     * @param array<string, mixed> $message
     */
    private function receive(array $message): void {
        $this->service->handle([
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => ['metadata' => ['phone_number_id' => self::PHONE_NUMBER_ID], 'messages' => [$message]],
                ]],
            ]],
        ], $this->credential);
    }

    private function assertReplied(string $expectedText): void {
        Http::assertSent(fn (Request $request): bool => $request['to'] === self::WA_ID
            && str_contains((string) $request['text']['body'], $expectedText));
    }

    /**
     * Each message gets a fresh id, so the 7-day deduplication never carries over between tests or runs.
     *
     * @return array{from: string, id: string, timestamp: string, type: string, text: array{body: string}}
     */
    private function textMessage(string $body): array {
        return [
            'from' => self::WA_ID,
            'id' => 'wamid.'.Str::uuid()->toString(),
            'timestamp' => (string) now()->getTimestamp(),
            'type' => 'text',
            'text' => ['body' => $body],
        ];
    }
}
