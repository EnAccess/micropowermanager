<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\ResendRejectedMessages;
use App\Events\TransactionSuccessfulEvent;
use App\Jobs\SmsProcessor;
use App\Models\Address\Address;
use App\Models\Device;
use App\Models\MainSettings;
use App\Models\MpmPlugin;
use App\Models\Person\Person;
use App\Models\Plugins;
use App\Models\Sms;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use App\Plugins\WhatsappBusinessApi\Exceptions\MessageNotSentException;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappBusinessOptIn;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappCredential;
use Database\Factories\Address\AddressFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\UserFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Testing\Fakes\QueueFake;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

/**
 * End-to-end simulation of the transactional STS electricity token delivery
 * pipeline using the WhatsApp Business API Cloud Gateway.
 */
class WhatsappTransactionTokenDeliverySimulationTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string PHONE_NUMBER = '+255712345678';
    private const string CLEAN_PHONE = '255712345678';
    private const string STS_TOKEN = '5839-2948-1029-4820-9182';
    private const string METER_SERIAL = 'MTR-STS-883921';
    private const string PHONE_NUMBER_ID = '109876543210';
    private const string META_ACCESS_TOKEN = 'meta-test-access-token-123';
    private const string TEMPLATE_NAME = 'mpm_sts_token_delivery_v1';
    private const string WEBHOOK_SECRET = 'simulation-webhook-secret';
    private const string GRAPH_API_URL = 'https://graph.facebook.com/v18.0/109876543210/messages';

    protected function setUp(): void {
        parent::setUp();

        // 1. Ensure user exists for tenant context resolution in AbstractJob
        UserFactory::new()->create(['company_id' => $this->companyId]);

        // 2. Configure MainSettings for WhatsApp SMS gateway and TZS currency
        MainSettings::query()->firstOrFail()->update([
            'sms_gateway_id' => MpmPlugin::WHATSAPP_BUSINESS_API,
            'currency' => 'TZS',
            'transaction_sms_enabled' => 1,
        ]);

        // 3. Mark WhatsApp Business API plugin as active
        Plugins::query()->updateOrCreate(
            ['mpm_plugin_id' => MpmPlugin::WHATSAPP_BUSINESS_API],
            ['status' => Plugins::ACTIVE],
        );

        // 4. Save encrypted credentials
        WhatsappCredential::query()->updateOrCreate(['id' => 1], [
            'access_token' => Crypt::encryptString(self::META_ACCESS_TOKEN),
            'phone_number_id' => Crypt::encryptString(self::PHONE_NUMBER_ID),
            'waba_id' => Crypt::encryptString('987654321098'),
            'webhook_secret' => Crypt::encryptString(self::WEBHOOK_SECRET),
            'transaction_confirmation_template' => self::TEMPLATE_NAME,
            'reminder_template' => 'mpm_payment_reminder_v1',
        ]);
    }

    /**
     * Simulation Scenario 1:
     * Full purchase lifecycle -> Transaction & STS Token created -> Event fired ->
     * Notification queued -> Processed via Meta Graph API -> SMS marked SENT ->
     * Webhook delivery status callback received.
     */
    public function testFullEndToEndStsTokenDeliveryViaWhatsapp(): void {
        Queue::fake();

        Http::fake([
            self::GRAPH_API_URL => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => self::CLEAN_PHONE, 'wa_id' => self::CLEAN_PHONE]],
                'messages' => [['id' => 'wamid.HBgLMTIzNDU2Nzg5MA==']],
            ], 200),
        ]);

        [$person, $device, $transaction, $token] = $this->createStsPurchaseTransaction(isOptedIn: true);

        // Step 1: Fire TransactionSuccessfulEvent
        event(new TransactionSuccessfulEvent($transaction));

        // Step 2: Verify SmsProcessor job was queued
        Queue::assertPushed(SmsProcessor::class, 1);
        $job = $this->getPushedSmsProcessor();

        // Verify initial SMS record is stored
        $this->assertDatabaseHas('sms', [
            'trigger_type' => 'transaction',
            'trigger_id' => $transaction->id,
            'receiver' => self::PHONE_NUMBER,
            'status' => Sms::STATUS_STORED,
        ], 'tenant');

        // Step 3: Execute the queued job
        $job->handle();

        // Step 4: Verify Meta Graph API received the exact template payload
        Http::assertSent(function (Request $request): bool {
            return $request->url() === self::GRAPH_API_URL
                && $request->hasHeader('Authorization', 'Bearer '.self::META_ACCESS_TOKEN)
                && $request['messaging_product'] === 'whatsapp'
                && $request['to'] === self::CLEAN_PHONE
                && $request['type'] === 'template'
                && $request['template']['name'] === self::TEMPLATE_NAME
                && $request['template']['components'][0]['parameters'][0]['text'] === '5000 TZS'
                && $request['template']['components'][0]['parameters'][1]['text'] === self::STS_TOKEN;
        });

        // Step 5: Verify SMS state in database transitioned to SENT
        $this->assertDatabaseHas('sms', [
            'trigger_type' => 'transaction',
            'trigger_id' => $transaction->id,
            'receiver' => self::PHONE_NUMBER,
            'status' => Sms::STATUS_SENT,
            'gateway_id' => MpmPlugin::WHATSAPP_BUSINESS_API,
            'attempts' => 1,
            'error_message' => null,
        ], 'tenant');

        // Step 6: Simulate incoming delivery status webhook from Meta
        $webhookUrl = '/api/whatsapp-business-api/webhook/'.$this->companyId;
        $webhookPayload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '987654321098',
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => '15550234567',
                                    'phone_number_id' => self::PHONE_NUMBER_ID,
                                ],
                                'statuses' => [
                                    [
                                        'id' => 'wamid.HBgLMTIzNDU2Nzg5MA==',
                                        'status' => 'delivered',
                                        'timestamp' => (string) time(),
                                        'recipient_id' => self::CLEAN_PHONE,
                                    ],
                                ],
                            ],
                            'field' => 'messages',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson($webhookUrl, $webhookPayload);
        $response->assertStatus(200);
        $this->assertSame('EVENT_RECEIVED', $response->getContent());
    }

    /**
     * Simulation Scenario 2:
     * Customer has NOT opted in -> Sending blocked to comply with Meta anti-spam policies ->
     * SMS marked FAILED -> Underlying power transaction & STS token remain completely intact.
     */
    public function testDeliveryBlockedWhenCustomerNotOptedInPreservesEnergyTransaction(): void {
        Queue::fake();

        [$person, $device, $transaction, $token] = $this->createStsPurchaseTransaction(isOptedIn: false);

        event(new TransactionSuccessfulEvent($transaction));

        Queue::assertPushed(SmsProcessor::class, 1);
        $job = $this->getPushedSmsProcessor();

        try {
            $job->handle();
            $this->fail('Expected MessageNotSentException was not thrown.');
        } catch (MessageNotSentException $exception) {
            $job->failed($exception);
        }

        // Verify SMS record marked FAILED with explanation
        $this->assertDatabaseHas('sms', [
            'trigger_type' => 'transaction',
            'trigger_id' => $transaction->id,
            'status' => Sms::STATUS_FAILED,
            'error_message' => 'Customer has not opted in for WhatsApp messages',
        ], 'tenant');

        // CRITICAL INTEGRITY CHECK: Transaction & Token must remain valid
        $freshTransaction = $transaction->fresh();
        $this->assertNotNull($freshTransaction);
        $this->assertSame(5000, (int) $freshTransaction->amount);

        $freshToken = $token->fresh();
        $this->assertNotNull($freshToken);
        $this->assertSame(self::STS_TOKEN, $freshToken->token);
        $this->assertSame(18.5, (float) $freshToken->token_amount);
    }

    /**
     * Simulation Scenario 3:
     * Meta Cloud API outage / rate limit -> Graceful failure handling ->
     * SMS marked FAILED -> Customer transaction & token remain intact.
     */
    public function testMetaCloudApiOutageMarksSmsFailedWithoutImpactingCustomerToken(): void {
        Queue::fake();

        Http::fake([
            self::GRAPH_API_URL => Http::response([
                'error' => [
                    'message' => 'Service temporarily unavailable due to high load',
                    'type' => 'OAuthException',
                    'code' => 500,
                ],
            ], 500),
        ]);

        [$person, $device, $transaction, $token] = $this->createStsPurchaseTransaction(isOptedIn: true);

        event(new TransactionSuccessfulEvent($transaction));

        $job = $this->getPushedSmsProcessor();

        try {
            $job->handle();
            $this->fail('Expected MessageNotSentException was not thrown.');
        } catch (MessageNotSentException $exception) {
            $job->failed($exception);
        }

        // SMS marked failed
        $sms = Sms::query()->where('trigger_id', $transaction->id)->firstOrFail();
        $this->assertSame(Sms::STATUS_FAILED, $sms->status);
        $this->assertStringContainsString('WhatsApp message sending failed', (string) $sms->error_message);

        // Power transaction remains intact
        $this->assertSame(5000, (int) $transaction->fresh()->amount);
        $this->assertSame(self::STS_TOKEN, $token->fresh()->token);
    }

    /**
     * Simulation Scenario 4:
     * Recovery workflow: Failed delivery can be automatically resent via
     * `sms:resend-rejected` after the customer opts in.
     */
    public function testResendRejectedMessagesCommandRecoversFailedStsDeliveryOnceCustomerOptsIn(): void {
        Http::fake([
            self::GRAPH_API_URL => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.HBgLMTIzNDU2Nzg5MA==']],
            ], 200),
        ]);

        // Create transaction where customer was initially not opted in
        [$person, $device, $transaction, $token] = $this->createStsPurchaseTransaction(isOptedIn: false);

        // Pre-create failed SMS in database
        $sms = Sms::query()->create([
            'trigger_type' => 'transaction',
            'trigger_id' => $transaction->id,
            'receiver' => self::PHONE_NUMBER,
            'body' => 'Dear Amina Mwangi, Token: '.self::STS_TOKEN,
            'direction' => Sms::DIRECTION_OUTGOING,
            'status' => Sms::STATUS_FAILED,
            'attempts' => 1,
            'error_message' => 'Customer has not opted in for WhatsApp messages',
        ]);

        // Customer now opts in
        WhatsappBusinessOptIn::query()->updateOrCreate(
            ['person_id' => $person->id],
            ['opted_in' => true],
        );

        // Run resend command
        $exitCode = $this->runResendCommand();
        $this->assertSame(0, $exitCode);

        // Verify SMS has transitioned to STATUS_SENT with attempts = 2
        $sms->refresh();
        $this->assertSame(Sms::STATUS_SENT, $sms->status);
        $this->assertSame(MpmPlugin::WHATSAPP_BUSINESS_API, $sms->gateway_id);
        $this->assertNull($sms->error_message);
        $this->assertSame(2, $sms->attempts);

        // Verify Meta API was called
        Http::assertSent(fn (Request $r): bool => $r->url() === self::GRAPH_API_URL && $r['to'] === self::CLEAN_PHONE);
    }

    /**
     * Simulation Scenario 5:
     * Idempotency & deduplication: Replaying TransactionSuccessfulEvent for an
     * already-processed transaction does not trigger a duplicate SMS.
     */
    public function testReplayedTransactionSuccessEventDoesNotDuplicateWhatsappNotification(): void {
        Queue::fake();

        [$person, $device, $transaction, $token] = $this->createStsPurchaseTransaction(isOptedIn: true);

        // First event
        event(new TransactionSuccessfulEvent($transaction));
        Queue::assertPushed(SmsProcessor::class, 1);

        // Second event (e.g. queue retry of event dispatcher)
        event(new TransactionSuccessfulEvent($transaction));

        // Still only 1 pushed job
        Queue::assertPushed(SmsProcessor::class, 1);
        $this->assertSame(1, Sms::query()->where('trigger_id', $transaction->id)->count());
    }

    /**
     * Helper to create all database records needed for an STS electricity token purchase.
     *
     * @return array{0: Person, 1: Device, 2: Transaction, 3: Token}
     */
    private function createStsPurchaseTransaction(bool $isOptedIn = true): array {
        $person = PersonFactory::new()->create([
            'name' => 'Amina',
            'surname' => 'Mwangi',
        ]);

        $address = AddressFactory::new()->make([
            'phone' => self::PHONE_NUMBER,
            'is_primary' => 1,
        ]);
        $address->owner()->associate($person);
        $address->save();

        WhatsappBusinessOptIn::query()->updateOrCreate(
            ['person_id' => $person->id],
            ['opted_in' => $isOptedIn],
        );

        $device = Device::query()->create([
            'person_id' => $person->id,
            'device_type' => 'meter',
            'device_id' => 1,
            'device_serial' => self::METER_SERIAL,
        ]);

        $transaction = Transaction::query()->create([
            'original_transaction_id' => 101,
            'original_transaction_type' => 'cash_transaction',
            'amount' => 5000,
            'type' => Transaction::TYPE_ENERGY,
            'sender' => self::PHONE_NUMBER,
            'message' => self::METER_SERIAL,
        ]);

        $token = Token::query()->create([
            'transaction_id' => $transaction->id,
            'device_id' => $device->id,
            'token' => self::STS_TOKEN,
            'token_type' => Token::TYPE_ENERGY,
            'token_amount' => 18.5,
            'token_unit' => 'kWh',
        ]);

        return [$person, $device, $transaction, $token];
    }

    private function getPushedSmsProcessor(): SmsProcessor {
        /** @var QueueFake $queueFake */
        $queueFake = Queue::getFacadeRoot();
        /** @var SmsProcessor|null $job */
        $job = $queueFake->pushed(SmsProcessor::class)->first();
        $this->assertInstanceOf(SmsProcessor::class, $job);

        return $job;
    }

    private function runResendCommand(): int {
        $command = $this->app->make(ResendRejectedMessages::class);
        $command->setLaravel($this->app);

        return $command->run(
            new ArrayInput(['amount' => '5', '--company-id' => (string) $this->companyId]),
            new BufferedOutput(),
        );
    }
}
