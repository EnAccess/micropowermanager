<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\ResendRejectedMessages;
use App\Jobs\SmsProcessor;
use App\Models\Address\Address;
use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use App\Models\MainSettings;
use App\Models\MpmPlugin;
use App\Models\Person\Person;
use App\Models\Plugins;
use App\Models\Sms;
use App\Models\User;
use App\Plugins\WhatsappBusinessApi\Exceptions\MessageNotSentException;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappBusinessOptIn;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappCredential;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Carbon\Carbon;
use Database\Factories\Address\AddressFactory;
use Database\Factories\ApplianceFactory;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceRateFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\SmsApplianceRemindRateFactory;
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
 * End-to-end simulation of the appliance installment debt payment reminder
 * notification pipeline using the WhatsApp Business API Cloud Gateway.
 */
class WhatsappApplianceReminderSimulationTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string PHONE_NUMBER = '+255712345678';
    private const string CLEAN_PHONE = '255712345678';
    private const string PHONE_NUMBER_ID = '109876543210';
    private const string META_ACCESS_TOKEN = 'meta-test-access-token-123';
    private const string REMINDER_TEMPLATE_NAME = 'mpm_payment_reminder_v1';
    private const string WEBHOOK_SECRET = 'simulation-webhook-secret';
    private const string GRAPH_API_URL = 'https://graph.facebook.com/v18.0/109876543210/messages';

    private User $user;

    protected function setUp(): void {
        parent::setUp();

        // 1. Ensure user exists for tenant context resolution in AbstractJob
        $this->user = UserFactory::new()->create(['company_id' => $this->companyId]);

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
            'transaction_confirmation_template' => 'mpm_sts_token_delivery_v1',
            'reminder_template' => self::REMINDER_TEMPLATE_NAME,
        ]);
    }

    /**
     * Simulation Scenario 1:
     * Upcoming Appliance Installment Debt Reminder ->
     * Rate evaluated -> Notification queued -> Meta Cloud Template payload sent ->
     * SMS marked SENT with link to ApplianceRate -> Webhook status callback received ->
     * Rate remind status marked REMIND_UPCOMING_SENT.
     */
    public function testUpcomingApplianceDebtReminderSentSuccessfullyViaWhatsapp(): void {
        Queue::fake();

        Http::fake([
            self::GRAPH_API_URL => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => self::CLEAN_PHONE, 'wa_id' => self::CLEAN_PHONE]],
                'messages' => [['id' => 'wamid.HBgLMTIzNDU2Nzg5MA==']],
            ], 200),
        ]);

        [$person, $appliancePerson, $applianceRate] = $this->createApplianceDebtEnvironment(
            dueDate: '2026-10-15',
            remaining: 15000,
            isOptedIn: true,
        );

        // Step 1: Dispatch upcoming reminder via core SmsService
        $smsService = app()->make(SmsService::class);
        $smsService->sendSms($applianceRate, SmsTypes::APPLIANCE_RATE, SmsConfigs::class);

        // Step 2: Verify SmsProcessor job was queued
        Queue::assertPushed(SmsProcessor::class, 1);
        $job = $this->getPushedSmsProcessor();

        // Verify initial SMS record is stored with polymorphic linkage to appliance_rate
        $this->assertDatabaseHas('sms', [
            'trigger_type' => 'appliance_rate',
            'trigger_id' => $applianceRate->id,
            'receiver' => self::PHONE_NUMBER,
            'status' => Sms::STATUS_STORED,
        ], 'tenant');

        // Step 3: Execute the queued job
        $job->handle();

        // Step 4: Verify Meta Graph API received the exact reminder template payload
        Http::assertSent(function (Request $request): bool {
            return $request->url() === self::GRAPH_API_URL
                && $request->hasHeader('Authorization', 'Bearer '.self::META_ACCESS_TOKEN)
                && $request['messaging_product'] === 'whatsapp'
                && $request['to'] === self::CLEAN_PHONE
                && $request['type'] === 'template'
                && $request['template']['name'] === self::REMINDER_TEMPLATE_NAME
                && $request['template']['components'][0]['parameters'][0]['text'] === '15000 TZS'
                && $request['template']['components'][0]['parameters'][1]['text'] === '2026-10-15';
        });

        // Step 5: Verify SMS state in database transitioned to SENT
        $this->assertDatabaseHas('sms', [
            'trigger_type' => 'appliance_rate',
            'trigger_id' => $applianceRate->id,
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
     * Overdue Appliance Installment Debt Reminder ->
     * Overdue rate evaluated -> Dispatched via OVER_DUE_APPLIANCE_RATE type ->
     * Delivered via Meta Graph API -> SMS marked SENT.
     */
    public function testOverdueApplianceDebtReminderSentSuccessfullyViaWhatsapp(): void {
        Queue::fake();

        Http::fake([
            self::GRAPH_API_URL => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => self::CLEAN_PHONE, 'wa_id' => self::CLEAN_PHONE]],
                'messages' => [['id' => 'wamid.HBgLMjM0NTY3ODkwMQ==']],
            ], 200),
        ]);

        [$person, $appliancePerson, $applianceRate] = $this->createApplianceDebtEnvironment(
            dueDate: '2026-10-01',
            remaining: 20000,
            isOptedIn: true,
        );

        $smsService = app()->make(SmsService::class);
        $smsService->sendSms($applianceRate, SmsTypes::OVER_DUE_APPLIANCE_RATE, SmsConfigs::class);

        Queue::assertPushed(SmsProcessor::class, 1);
        $job = $this->getPushedSmsProcessor();
        $job->handle();

        Http::assertSent(function (Request $request): bool {
            return $request->url() === self::GRAPH_API_URL
                && $request['to'] === self::CLEAN_PHONE
                && $request['template']['name'] === self::REMINDER_TEMPLATE_NAME
                && $request['template']['components'][0]['parameters'][0]['text'] === '20000 TZS'
                && $request['template']['components'][0]['parameters'][1]['text'] === '2026-10-01';
        });

        $this->assertDatabaseHas('sms', [
            'trigger_type' => 'appliance_rate',
            'trigger_id' => $applianceRate->id,
            'status' => Sms::STATUS_SENT,
            'gateway_id' => MpmPlugin::WHATSAPP_BUSINESS_API,
        ], 'tenant');
    }

    /**
     * Simulation Scenario 3:
     * Customer has NOT opted in -> Sending blocked to comply with Meta anti-spam policies ->
     * SMS marked FAILED -> Underlying appliance debt contract & installment balance remain completely intact.
     */
    public function testReminderBlockedWhenCustomerNotOptedInPreservesApplianceContract(): void {
        Queue::fake();

        [$person, $appliancePerson, $applianceRate] = $this->createApplianceDebtEnvironment(
            dueDate: '2026-10-15',
            remaining: 18000,
            isOptedIn: false,
        );

        $smsService = app()->make(SmsService::class);
        $smsService->sendSms($applianceRate, SmsTypes::APPLIANCE_RATE, SmsConfigs::class);

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
            'trigger_type' => 'appliance_rate',
            'trigger_id' => $applianceRate->id,
            'status' => Sms::STATUS_FAILED,
            'error_message' => 'Customer has not opted in for WhatsApp messages',
        ], 'tenant');

        // CRITICAL INTEGRITY CHECK: Appliance debt contract and installment schedule must remain intact
        $freshRate = $applianceRate->fresh();
        $this->assertNotNull($freshRate);
        $this->assertSame(18000, (int) $freshRate->remaining);
        $this->assertSame('2026-10-15', Carbon::parse($freshRate->due_date)->toDateString());

        $freshContract = $appliancePerson->fresh();
        $this->assertNotNull($freshContract);
        $this->assertSame($person->id, $freshContract->person_id);
    }

    /**
     * Simulation Scenario 4:
     * Meta Cloud API outage / rate limit -> Graceful failure handling ->
     * SMS marked FAILED -> Customer debt schedule remains untouched.
     */
    public function testMetaCloudApiOutageMarksReminderFailedWithoutImpactingApplianceDebt(): void {
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

        [$person, $appliancePerson, $applianceRate] = $this->createApplianceDebtEnvironment(
            dueDate: '2026-10-15',
            remaining: 14000,
            isOptedIn: true,
        );

        $smsService = app()->make(SmsService::class);
        $smsService->sendSms($applianceRate, SmsTypes::APPLIANCE_RATE, SmsConfigs::class);

        $job = $this->getPushedSmsProcessor();

        try {
            $job->handle();
            $this->fail('Expected MessageNotSentException was not thrown.');
        } catch (MessageNotSentException $exception) {
            $job->failed($exception);
        }

        $sms = Sms::query()->where('trigger_id', $applianceRate->id)->firstOrFail();
        $this->assertSame(Sms::STATUS_FAILED, $sms->status);
        $this->assertStringContainsString('WhatsApp message sending failed', (string) $sms->error_message);

        // Debt record remains intact
        $this->assertSame(14000, (int) $applianceRate->fresh()->remaining);
    }

    /**
     * Simulation Scenario 5:
     * Recovery workflow: Failed reminder delivery can be automatically resent via
     * `sms:resend-rejected` after customer opts in.
     */
    public function testResendRejectedMessagesCommandRecoversFailedReminderOnceCustomerOptsIn(): void {
        Http::fake([
            self::GRAPH_API_URL => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.HBgLMjM0NTY3ODkwMQ==']],
            ], 200),
        ]);

        [$person, $appliancePerson, $applianceRate] = $this->createApplianceDebtEnvironment(
            dueDate: '2026-10-15',
            remaining: 12000,
            isOptedIn: false,
        );

        // Pre-create failed SMS in database
        $sms = Sms::query()->create([
            'trigger_type' => 'appliance_rate',
            'trigger_id' => $applianceRate->id,
            'receiver' => self::PHONE_NUMBER,
            'body' => 'Dear Fatuma Bakari, reminder for appliance due date: 2026-10-15',
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
     * Helper to set up a complete appliance financing debt contract in tenant DB.
     *
     * @return array{0: Person, 1: AppliancePerson, 2: ApplianceRate}
     */
    private function createApplianceDebtEnvironment(
        string $dueDate,
        int $remaining,
        bool $isOptedIn = true,
    ): array {
        $person = PersonFactory::new()->create([
            'name' => 'Fatuma',
            'surname' => 'Bakari',
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

        $applianceType = ApplianceTypeFactory::new()->create([
            'name' => 'Solar Refrigerator 200L',
        ]);

        $appliance = ApplianceFactory::new()->create([
            'appliance_type_id' => $applianceType->id,
        ]);

        $appliancePerson = AppliancePersonFactory::new()->create([
            'person_id' => $person->id,
            'appliance_id' => $appliance->id,
            'total_cost' => 150000,
            'rate_count' => 10,
            'creator_type' => 'user',
            'creator_id' => $this->user->id,
        ]);

        SmsApplianceRemindRateFactory::new()->create([
            'appliance_id' => $appliance->id,
            'remind_rate' => 7,
            'overdue_remind_rate' => 14,
            'upcoming_reminder_enabled' => true,
            'overdue_reminder_enabled' => true,
        ]);

        $applianceRate = ApplianceRateFactory::new()->create([
            'appliance_person_id' => $appliancePerson->id,
            'rate_cost' => $remaining,
            'remaining' => $remaining,
            'due_date' => $dueDate,
            'remind' => ApplianceRate::REMIND_NONE,
        ]);

        return [$person, $appliancePerson, $applianceRate];
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
