<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\SmsStoredEvent;
use App\Jobs\SmsProcessor;
use App\Models\Address\Address;
use App\Models\Device;
use App\Models\MainSettings;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterConsumption;
use App\Models\MpmPlugin;
use App\Models\Person\Person;
use App\Models\Plugins;
use App\Models\Sms;
use App\Models\SmsResendInformationKey;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketCategory;
use App\Models\Token;
use App\Models\User;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappBusinessOptIn;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappCredential;
use App\Plugins\WhatsappBusinessApi\Services\WhatsappSelfService;
use Database\Factories\Address\AddressFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\UserFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Testing\Fakes\QueueFake;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

/**
 * High-level simulation suite for Inbound Customer Support & Two-Way Messaging
 * over the WhatsApp Business API Cloud Gateway.
 */
class WhatsappInboundCustomerSupportSimulationTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string CUSTOMER_PHONE = '+255712345678';
    private const string CLEAN_PHONE = '255712345678';
    private const string STRANGER_PHONE = '+255799112233';
    private const string PHONE_NUMBER_ID = '109876543210';
    private const string META_ACCESS_TOKEN = 'meta-test-access-token-123';
    private const string WEBHOOK_SECRET = 'simulation-webhook-secret';
    private const string GRAPH_API_URL = 'https://graph.facebook.com/v18.0/109876543210/messages';

    private User $operatorUser;
    private string $webhookUrl;

    protected function setUp(): void {
        parent::setUp();

        $this->webhookUrl = '/api/whatsapp-business-api/webhook/'.$this->companyId;

        // 1. Create an authenticated Operator / Support Agent user
        $this->operatorUser = UserFactory::new()->create(['company_id' => $this->companyId]);

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
            'reminder_template' => 'mpm_payment_reminder_v1',
        ]);

        // 5. Default fake for Meta Graph API endpoints
        Http::fake([
            self::GRAPH_API_URL => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => self::CLEAN_PHONE, 'wa_id' => self::CLEAN_PHONE]],
                'messages' => [['id' => 'wamid.MOCK_SIMULATION_RESP_001']],
            ], 200),
        ]);
    }

    /**
     * Simulation Scenario 1:
     * Inbound Customer Support Inquiry -> Meta Webhook ingestion ->
     * Address matched to existing customer -> Inbound SMS record created ->
     * Visible in operator's SMS conversations view (/api/sms).
     */
    public function testInboundCustomerSupportMessageIngestedAndVisibleInConversations(): void {
        $customer = $this->createCustomer('Fatuma', 'Bakari', self::CUSTOMER_PHONE);

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
                                'contacts' => [
                                    [
                                        'profile' => ['name' => 'Fatuma Bakari'],
                                        'wa_id' => self::CLEAN_PHONE,
                                    ],
                                ],
                                'messages' => [
                                    [
                                        'from' => self::CLEAN_PHONE,
                                        'id' => 'wamid.INBOUND_SUPPORT_001',
                                        'timestamp' => (string) time(),
                                        'type' => 'text',
                                        'text' => [
                                            'body' => 'Hello, what are your minigrid operating hours?',
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

        // Webhook ingestion
        $response = $this->postJson($this->webhookUrl, $webhookPayload);
        $response->assertStatus(200);
        $this->assertSame('EVENT_RECEIVED', $response->getContent());

        // Verify incoming SMS record in database
        $this->assertDatabaseHas('sms', [
            'receiver' => self::CUSTOMER_PHONE,
            'body' => 'Hello, what are your minigrid operating hours?',
            'sender_id' => $customer->id,
            'direction' => Sms::DIRECTION_INCOMING,
            'status' => Sms::STATUS_DELIVERED,
            'gateway_id' => MpmPlugin::WHATSAPP_BUSINESS_API,
            'uuid' => 'wamid.INBOUND_SUPPORT_001',
        ], 'tenant');

        // Verify the conversation is immediately visible to operators querying /api/sms
        $conversationsResponse = $this->actingAs($this->operatorUser)->getJson('/api/sms');
        $conversationsResponse->assertStatus(200);

        $data = $conversationsResponse->json('data');
        $this->assertIsArray($data);
        $conversationReceivers = array_column($data, 'receiver');
        $this->assertContains(self::CUSTOMER_PHONE, $conversationReceivers);
    }

    /**
     * Simulation Scenario 2:
     * Two-Way Dialogue: Operator replies to customer from MPM dashboard ->
     * Free-form text message dispatched via Meta Cloud API ->
     * SMS marked SENT -> Full conversation thread preserved.
     */
    public function testOperatorTwoWayReplyDispatchesWhatsappTextMessageToCustomer(): void {
        Queue::fake();

        Http::fake([
            self::GRAPH_API_URL => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => self::CLEAN_PHONE, 'wa_id' => self::CLEAN_PHONE]],
                'messages' => [['id' => 'wamid.OUTBOUND_REPLY_002']],
            ], 200),
        ]);

        $customer = $this->createCustomer('Fatuma', 'Bakari', self::CUSTOMER_PHONE, isOptedIn: true);

        // Pre-create customer's initial inquiry
        Sms::query()->create([
            'receiver' => self::CUSTOMER_PHONE,
            'body' => 'Can someone assist me with my account?',
            'sender_id' => $customer->id,
            'direction' => Sms::DIRECTION_INCOMING,
            'status' => Sms::STATUS_DELIVERED,
            'gateway_id' => MpmPlugin::WHATSAPP_BUSINESS_API,
        ]);

        // Operator sends reply via /api/sms/storeandsend
        $operatorMessage = 'Hello Fatuma, minigrid support is active 24/7.';
        $replyResponse = $this->actingAs($this->operatorUser)->postJson('/api/sms/storeandsend', [
            'person_id' => $customer->id,
            'message' => $operatorMessage,
            'senderId' => $this->operatorUser->id,
        ]);

        $replyResponse->assertStatus(201);

        // Verify SmsProcessor was pushed and handle it
        Queue::assertPushed(SmsProcessor::class, 1);
        $job = $this->getPushedSmsProcessor();
        $job->handle();

        // Verify Meta Graph API received free-form text payload
        Http::assertSent(function (Request $request) use ($operatorMessage): bool {
            return $request->url() === self::GRAPH_API_URL
                && $request['messaging_product'] === 'whatsapp'
                && $request['to'] === self::CLEAN_PHONE
                && $request['type'] === 'text'
                && $request['text']['body'] === $operatorMessage;
        });

        // Verify outbound SMS record transitioned to STATUS_SENT
        $this->assertDatabaseHas('sms', [
            'receiver' => self::CUSTOMER_PHONE,
            'body' => $operatorMessage,
            'direction' => Sms::DIRECTION_OUTGOING,
            'status' => Sms::STATUS_SENT,
            'gateway_id' => MpmPlugin::WHATSAPP_BUSINESS_API,
        ], 'tenant');
    }

    /**
     * Simulation Scenario 3:
     * Inbound Self-Service Command: Customer texts "resend MTR-STS-883921" ->
     * Webhook fires SmsStoredEvent -> Core SmsListener intercepts ->
     * Automatically dispatches latest STS token to customer over WhatsApp!
     */
    public function testInboundSelfServiceTokenResendCommandTriggersAutomatedResponse(): void {
        Event::fake([SmsStoredEvent::class]);

        $customer = $this->createCustomer('Fatuma', 'Bakari', self::CUSTOMER_PHONE, isOptedIn: true);

        // Set up active resend keyword in database
        SmsResendInformationKey::query()->create(['key' => 'resend']);

        $meterSerial = 'MTR-STS-883921';

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
                                'messages' => [
                                    [
                                        'from' => self::CLEAN_PHONE,
                                        'id' => 'wamid.INBOUND_RESEND_003',
                                        'timestamp' => (string) time(),
                                        'type' => 'text',
                                        'text' => [
                                            'body' => 'resend '.$meterSerial,
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

        $response = $this->postJson($this->webhookUrl, $webhookPayload);
        $response->assertStatus(200);

        // Assert incoming message recorded
        $this->assertDatabaseHas('sms', [
            'receiver' => self::CUSTOMER_PHONE,
            'body' => 'resend '.$meterSerial,
            'direction' => Sms::DIRECTION_INCOMING,
            'status' => Sms::STATUS_DELIVERED,
        ], 'tenant');

        // Assert SmsStoredEvent dispatched with the exact sender and command body
        Event::assertDispatched(SmsStoredEvent::class, function (SmsStoredEvent $event) use ($meterSerial): bool {
            return $event->sender === self::CUSTOMER_PHONE
                && $event->message === 'resend '.$meterSerial
                && $event->sms instanceof Sms;
        });
    }

    /**
     * Simulation Scenario 4:
     * Delivery Receipt Tracking: Meta Webhook status callback matches outbound SMS UUID ->
     * Status updated to STATUS_DELIVERED in tenant database.
     */
    public function testInboundDeliveryStatusWebhookUpdatesSmsRecordToDelivered(): void {
        $outboundWamid = 'wamid.OUTBOUND_TRACKED_999';

        $sms = Sms::query()->create([
            'receiver' => self::CUSTOMER_PHONE,
            'body' => 'Your monthly energy report is ready.',
            'direction' => Sms::DIRECTION_OUTGOING,
            'status' => Sms::STATUS_SENT,
            'gateway_id' => MpmPlugin::WHATSAPP_BUSINESS_API,
            'uuid' => $outboundWamid,
        ]);

        $deliveryWebhookPayload = [
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
                                        'id' => $outboundWamid,
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

        $response = $this->postJson($this->webhookUrl, $deliveryWebhookPayload);
        $response->assertStatus(200);

        $sms->refresh();
        $this->assertSame(Sms::STATUS_DELIVERED, $sms->status);
    }

    /**
     * Simulation Scenario 5:
     * Unregistered / Stranger Recipient Inbound Message ->
     * Gracefully ingested with sender_id = null ->
     * Appears in /api/sms so operators can identify new prospect inquiries.
     */
    public function testStrangerInboundMessageRecordedAsDeliveredWithoutCrashing(): void {
        $strangerPayload = [
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
                                'messages' => [
                                    [
                                        'from' => '255799112233',
                                        'id' => 'wamid.STRANGER_INBOUND_005',
                                        'timestamp' => (string) time(),
                                        'type' => 'text',
                                        'text' => [
                                            'body' => 'Hello, I want to connect my shop to the minigrid.',
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

        $response = $this->postJson($this->webhookUrl, $strangerPayload);
        $response->assertStatus(200);

        // Verify stored in database with null sender_id
        $this->assertDatabaseHas('sms', [
            'receiver' => self::STRANGER_PHONE,
            'body' => 'Hello, I want to connect my shop to the minigrid.',
            'sender_id' => null,
            'direction' => Sms::DIRECTION_INCOMING,
            'status' => Sms::STATUS_DELIVERED,
            'gateway_id' => MpmPlugin::WHATSAPP_BUSINESS_API,
        ], 'tenant');

        // Stranger conversation is visible in conversations list
        $conversationsResponse = $this->actingAs($this->operatorUser)->getJson('/api/sms');
        $data = $conversationsResponse->json('data');
        $this->assertIsArray($data);
        $conversationReceivers = array_column($data, 'receiver');
        $this->assertContains(self::STRANGER_PHONE, $conversationReceivers);
    }

    /**
     * Simulation Scenario 6:
     * Customer sends meaningless or unspecified words (e.g. 'hello', 'asdfgh') ->
     * Automatically dispatches interactive menu page containing the 4 options/buttons:
     * 1. Get latest token
     * 2. Did not receive token
     * 3. Status for low balance
     * 4. Meter info
     * -> Outgoing SMS record stored in database.
     */
    public function testMeaninglessOrUnspecifiedWordsTriggersInteractiveMenuWithButtons(): void {
        $customer = $this->createCustomer('Fatuma', 'Bakari', self::CUSTOMER_PHONE, isOptedIn: true);

        $payload = [
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
                                'messages' => [
                                    [
                                        'from' => self::CLEAN_PHONE,
                                        'id' => 'wamid.INBOUND_MEANINGLESS_001',
                                        'timestamp' => (string) time(),
                                        'type' => 'text',
                                        'text' => [
                                            'body' => 'random meaningless words asdfgh',
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

        // Verify incoming message stored
        $this->assertDatabaseHas('sms', [
            'receiver' => self::CUSTOMER_PHONE,
            'body' => 'random meaningless words asdfgh',
            'direction' => Sms::DIRECTION_INCOMING,
        ], 'tenant');

        // Verify Meta Graph API received interactive list payload containing the 4 options
        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== self::GRAPH_API_URL) {
                return false;
            }

            $data = $request->data();
            if (($data['type'] ?? '') !== 'interactive' || ($data['interactive']['type'] ?? '') !== 'list') {
                return false;
            }

            $rows = $data['interactive']['action']['sections'][0]['rows'] ?? [];
            $rowIds = array_column($rows, 'id');
            $rowTitles = array_column($rows, 'title');

            return in_array(WhatsappSelfService::ACTION_LATEST_TOKEN, $rowIds, true)
                && in_array(WhatsappSelfService::ACTION_NOT_RECEIVED_TOKEN, $rowIds, true)
                && in_array(WhatsappSelfService::ACTION_LOW_BALANCE, $rowIds, true)
                && in_array(WhatsappSelfService::ACTION_METER_INFO, $rowIds, true)
                && in_array('Get latest token', $rowTitles, true)
                && in_array('Did not receive token', $rowTitles, true)
                && in_array('Status for low balance', $rowTitles, true)
                && in_array('Meter info', $rowTitles, true);
        });

        // Verify outbound SMS record stored with menu summary
        $this->assertDatabaseHas('sms', [
            'receiver' => self::CUSTOMER_PHONE,
            'direction' => Sms::DIRECTION_OUTGOING,
            'gateway_id' => MpmPlugin::WHATSAPP_BUSINESS_API,
            'status' => Sms::STATUS_DELIVERED,
        ], 'tenant');
    }

    /**
     * Simulation Scenario 7:
     * Customer taps 'Get latest token' interactive button ->
     * System retrieves customer's token and responds with token code, units, and meter serial.
     */
    public function testInteractiveButtonClickGetLatestTokenReturnsTokenDetails(): void {
        $customer = $this->createCustomer('Fatuma', 'Bakari', self::CUSTOMER_PHONE, isOptedIn: true);

        // Create meter, device, and token
        $meter = Meter::query()->create([
            'serial_number' => 'MTR-TOKEN-7788',
            'meter_type_id' => 1,
            'manufacturer_id' => 1,
            'connection_type_id' => 1,
            'connection_group_id' => 1,
            'tariff_id' => 1,
            'in_use' => 1,
        ]);

        $device = Device::query()->create([
            'device_id' => $meter->id,
            'device_type' => 'meter',
            'person_id' => $customer->id,
            'device_serial' => 'MTR-TOKEN-7788',
        ]);

        $token = Token::query()->create([
            'token' => '4455-6677-8899-0011',
            'token_amount' => 25.5,
            'token_unit' => 'kWh',
            'device_id' => $device->id,
            'transaction_id' => 1,
        ]);

        $payload = [
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
                                'messages' => [
                                    [
                                        'from' => self::CLEAN_PHONE,
                                        'id' => 'wamid.INBOUND_BTN_TOKEN_001',
                                        'timestamp' => (string) time(),
                                        'type' => 'interactive',
                                        'interactive' => [
                                            'type' => 'list_reply',
                                            'list_reply' => [
                                                'id' => WhatsappSelfService::ACTION_LATEST_TOKEN,
                                                'title' => 'Get latest token',
                                            ],
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

        // Verify text message sent to WhatsApp containing the token details
        Http::assertSent(function (Request $request) use ($token): bool {
            if ($request->url() !== self::GRAPH_API_URL) {
                return false;
            }

            $body = $request->data()['text']['body'] ?? '';

            return str_contains($body, $token->token)
                && str_contains($body, 'MTR-TOKEN-7788')
                && str_contains($body, '25.5 kWh');
        });
    }

    /**
     * Simulation Scenario 8:
     * Customer taps 'Did not receive token' ->
     * If no active token found, automatically opens a support ticket in MPM
     * and responds to customer with ticket confirmation number!
     */
    public function testInteractiveButtonClickDidNotReceiveTokenCreatesSupportTicket(): void {
        $customer = $this->createCustomer('Fatuma', 'Bakari', self::CUSTOMER_PHONE, isOptedIn: true);

        // Ensure ticket category exists
        TicketCategory::query()->firstOrCreate(['id' => 1], [
            'label_name' => 'General Support',
            'label_color' => '#FF0000',
            'out_source' => 0,
        ]);

        $payload = [
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
                                'messages' => [
                                    [
                                        'from' => self::CLEAN_PHONE,
                                        'id' => 'wamid.INBOUND_BTN_MISSING_001',
                                        'timestamp' => (string) time(),
                                        'type' => 'interactive',
                                        'interactive' => [
                                            'type' => 'list_reply',
                                            'list_reply' => [
                                                'id' => WhatsappSelfService::ACTION_NOT_RECEIVED_TOKEN,
                                                'title' => 'Did not receive token',
                                            ],
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

        // Verify ticket created in database
        $this->assertDatabaseHas('tickets', [
            'owner_id' => $customer->id,
            'owner_type' => 'person',
            'status' => Ticket::STATUS['opened'],
        ], 'tenant');

        $createdTicket = Ticket::query()->where('owner_id', $customer->id)->latest('id')->firstOrFail();

        // Verify customer received WhatsApp text with ticket reference
        Http::assertSent(function (Request $request) use ($createdTicket): bool {
            if ($request->url() !== self::GRAPH_API_URL) {
                return false;
            }

            $body = $request->data()['text']['body'] ?? '';

            return str_contains($body, '#'.$createdTicket->id)
                && str_contains($body, 'Missing Token Ticket Created');
        });
    }

    /**
     * Simulation Scenario 9:
     * Customer taps 'Status for low balance' ->
     * Checks meter consumption credit and returns alert if credit is low.
     */
    public function testInteractiveButtonClickStatusForLowBalanceReturnsCreditBalance(): void {
        $customer = $this->createCustomer('Fatuma', 'Bakari', self::CUSTOMER_PHONE, isOptedIn: true);

        $meter = Meter::query()->create([
            'serial_number' => 'MTR-BALANCE-1122',
            'meter_type_id' => 1,
            'manufacturer_id' => 1,
            'connection_type_id' => 1,
            'connection_group_id' => 1,
            'tariff_id' => 1,
            'in_use' => 1,
        ]);

        Device::query()->create([
            'device_id' => $meter->id,
            'device_type' => 'meter',
            'person_id' => $customer->id,
            'device_serial' => 'MTR-BALANCE-1122',
        ]);

        // Create low balance consumption (2.4 kWh <= 5.0)
        MeterConsumption::query()->create([
            'meter_id' => $meter->id,
            'total_consumption' => 120.0,
            'consumption' => 2.0,
            'credit_on_meter' => 2.4,
            'reading_date' => now(),
        ]);

        $payload = [
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
                                'messages' => [
                                    [
                                        'from' => self::CLEAN_PHONE,
                                        'id' => 'wamid.INBOUND_BTN_BALANCE_001',
                                        'timestamp' => (string) time(),
                                        'type' => 'text',
                                        'text' => [
                                            'body' => 'status for low balance',
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

        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== self::GRAPH_API_URL) {
                return false;
            }

            $body = $request->data()['text']['body'] ?? '';

            return str_contains($body, 'Status for Low Balance')
                && str_contains($body, '2.4 kWh')
                && str_contains($body, 'Your credit is low');
        });
    }

    /**
     * Simulation Scenario 10:
     * Customer taps 'Meter info' ->
     * Returns registered meter details including serial number and active status.
     */
    public function testInteractiveButtonClickMeterInfoReturnsMeterDetails(): void {
        $customer = $this->createCustomer('Fatuma', 'Bakari', self::CUSTOMER_PHONE, isOptedIn: true);

        $meter = Meter::query()->create([
            'serial_number' => 'MTR-INFO-999000',
            'meter_type_id' => 1,
            'manufacturer_id' => 1,
            'connection_type_id' => 1,
            'connection_group_id' => 1,
            'tariff_id' => 1,
            'in_use' => 1,
        ]);

        Device::query()->create([
            'device_id' => $meter->id,
            'device_type' => 'meter',
            'person_id' => $customer->id,
            'device_serial' => 'MTR-INFO-999000',
        ]);

        $payload = [
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
                                'messages' => [
                                    [
                                        'from' => self::CLEAN_PHONE,
                                        'id' => 'wamid.INBOUND_BTN_INFO_001',
                                        'timestamp' => (string) time(),
                                        'type' => 'text',
                                        'text' => [
                                            'body' => 'meter info',
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

        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== self::GRAPH_API_URL) {
                return false;
            }

            $body = $request->data()['text']['body'] ?? '';

            return str_contains($body, 'Meter Information')
                && str_contains($body, 'MTR-INFO-999000')
                && str_contains($body, 'Fatuma Bakari');
        });
    }

    /**
     * Simulation Scenario 11:
     * Customer sends inbound keyword 'START' or 'OPTIN' ->
     * Opt-in is recorded as true in database ->
     * Dispatches confirmation text and interactive self-service menu.
     */
    public function testCustomerInboundStartKeywordActivatesOptInAndDispatchesMenu(): void {
        $customer = $this->createCustomer('Fatuma', 'Bakari', self::CUSTOMER_PHONE, isOptedIn: false);

        $payload = [
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
                                'messages' => [
                                    [
                                        'from' => self::CLEAN_PHONE,
                                        'id' => 'wamid.INBOUND_OPTIN_START_001',
                                        'timestamp' => (string) time(),
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

        $response = $this->postJson($this->webhookUrl, $payload);
        $response->assertStatus(200);

        // Verify database opt-in record transitioned to true
        $this->assertDatabaseHas('whatsapp_business_opt_ins', [
            'person_id' => $customer->id,
            'opted_in' => 1,
        ], 'tenant');

        // Verify confirmation message sent via WhatsApp
        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== self::GRAPH_API_URL) {
                return false;
            }

            $body = $request->data()['text']['body'] ?? '';

            return str_contains($body, 'Subscription Confirmed')
                && str_contains($body, 'Fatuma Bakari')
                && str_contains($body, 'STOP');
        });

        // Verify interactive menu was also dispatched
        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== self::GRAPH_API_URL) {
                return false;
            }

            $data = $request->data();

            return ($data['type'] ?? '') === 'interactive'
                && ($data['interactive']['type'] ?? '') === 'list';
        });
    }

    /**
     * Simulation Scenario 12:
     * Customer sends inbound keyword 'STOP' or 'OPTOUT' ->
     * Opt-in is set to false in database ->
     * Dispatches confirmation text stating future messages will be sent via SMS.
     */
    public function testCustomerInboundStopKeywordDeactivatesOptInAndConfirmsSmsFallback(): void {
        $customer = $this->createCustomer('Fatuma', 'Bakari', self::CUSTOMER_PHONE, isOptedIn: true);

        $payload = [
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
                                'messages' => [
                                    [
                                        'from' => self::CLEAN_PHONE,
                                        'id' => 'wamid.INBOUND_OPTOUT_STOP_001',
                                        'timestamp' => (string) time(),
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

        $response = $this->postJson($this->webhookUrl, $payload);
        $response->assertStatus(200);

        // Verify database opt-in record transitioned to false
        $this->assertDatabaseHas('whatsapp_business_opt_ins', [
            'person_id' => $customer->id,
            'opted_in' => 0,
        ], 'tenant');

        // Verify confirmation message sent informing of SMS fallback
        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== self::GRAPH_API_URL) {
                return false;
            }

            $body = $request->data()['text']['body'] ?? '';

            return str_contains($body, 'Unsubscribed Successfully')
                && str_contains($body, 'standard SMS')
                && str_contains($body, 'START');
        });
    }

    /**
     * Simulation Scenario 13:
     * Customer without prior opt-in sends general message ->
     * Conversational auto-opt-in sets opted_in = true.
     */
    public function testConversationalInboundMessageAutomaticallySetsOptInForPendingCustomer(): void {
        /** @var Person $customer */
        $customer = PersonFactory::new()->create([
            'name' => 'Asha',
            'surname' => 'Juma',
        ]);

        $address = AddressFactory::new()->make([
            'phone' => self::CUSTOMER_PHONE,
            'is_primary' => 1,
        ]);
        $address->owner()->associate($customer);
        $address->save();

        // Customer currently has NO opt-in record
        $this->assertDatabaseMissing('whatsapp_business_opt_ins', [
            'person_id' => $customer->id,
        ], 'tenant');

        $payload = [
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
                                'messages' => [
                                    [
                                        'from' => self::CLEAN_PHONE,
                                        'id' => 'wamid.INBOUND_CONVERSATIONAL_001',
                                        'timestamp' => (string) time(),
                                        'type' => 'text',
                                        'text' => [
                                            'body' => 'Good morning, minigrid office!',
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

        // Verify customer now has opted_in = 1 in database
        $this->assertDatabaseHas('whatsapp_business_opt_ins', [
            'person_id' => $customer->id,
            'opted_in' => 1,
        ], 'tenant');
    }

    private function createCustomer(string $name, string $surname, string $phone, bool $isOptedIn = true): Person {
        $person = PersonFactory::new()->create([
            'name' => $name,
            'surname' => $surname,
        ]);

        $address = AddressFactory::new()->make([
            'phone' => $phone,
            'is_primary' => 1,
        ]);
        $address->owner()->associate($person);
        $address->save();

        WhatsappBusinessOptIn::query()->updateOrCreate(
            ['person_id' => $person->id],
            ['opted_in' => $isOptedIn],
        );

        return $person;
    }

    private function getPushedSmsProcessor(): SmsProcessor {
        /** @var QueueFake $queueFake */
        $queueFake = Queue::getFacadeRoot();
        /** @var SmsProcessor|null $job */
        $job = $queueFake->pushed(SmsProcessor::class)->first();
        $this->assertInstanceOf(SmsProcessor::class, $job);

        return $job;
    }
}
