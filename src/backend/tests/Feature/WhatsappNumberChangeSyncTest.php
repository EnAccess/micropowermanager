<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Address\Address;
use App\Models\MpmPlugin;
use App\Models\Person\Person;
use App\Models\Plugins;
use App\Models\Sms;
use App\Plugins\AfricasTalking\AfricasTalkingGateway;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappBusinessOptIn;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappCredential;
use App\Services\SmsGatewayResolverService;
use Database\Factories\Address\AddressFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Crypt;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class WhatsappNumberChangeSyncTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string INITIAL_PHONE = '+255712345678';
    private const string NEW_PHONE_1 = '+255788123456';
    private const string NEW_PHONE_2 = '+255799887766';
    private const string NEW_PHONE_3 = '+255766554433';

    private string $webhookUrl;

    protected function setUp(): void {
        parent::setUp();

        $this->webhookUrl = '/api/whatsapp-business-api/webhook/'.$this->companyId;

        // Ensure WhatsApp plugin is registered active
        Plugins::query()->updateOrCreate(
            ['mpm_plugin_id' => MpmPlugin::WHATSAPP_BUSINESS_API],
            ['status' => Plugins::ACTIVE],
        );

        // Configure credentials
        WhatsappCredential::query()->updateOrCreate(['id' => 1], [
            'access_token' => Crypt::encryptString('meta-token-123'),
            'phone_number_id' => Crypt::encryptString('109876543210'),
            'waba_id' => Crypt::encryptString('987654321098'),
            'webhook_secret' => Crypt::encryptString('test-secret'),
        ]);
    }

    private function createCustomer(string $name, string $surname, string $phone): Person {
        /** @var Person $person */
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
            ['opted_in' => true],
        );

        return $person;
    }

    /**
     * Test 1: Customer sends inbound WhatsApp command: "UPDATE NUMBER +255788123456"
     * Result: Customer address phone is updated in MPM, opt-in is confirmed.
     */
    public function testInboundUpdateNumberCommandSyncsCustomerPhoneInMpm(): void {
        $customer = $this->createCustomer('John', 'Mtei', self::INITIAL_PHONE);

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
                                    'phone_number_id' => '109876543210',
                                ],
                                'messages' => [
                                    [
                                        'from' => '255712345678',
                                        'id' => 'wamid.HBgLInboundUpdateNumber01',
                                        'timestamp' => '1710000000',
                                        'type' => 'text',
                                        'text' => [
                                            'body' => 'UPDATE NUMBER '.self::NEW_PHONE_1,
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

        // Verify Address in MPM updated to NEW_PHONE_1
        $updatedAddress = Address::query()->where('owner_id', $customer->id)->first();
        $this->assertNotNull($updatedAddress);
        $this->assertSame(self::NEW_PHONE_1, (string) $updatedAddress->phone);

        // Verify Opt-In is true
        $optIn = WhatsappBusinessOptIn::query()->where('person_id', $customer->id)->first();
        $this->assertNotNull($optIn);
        $this->assertTrue($optIn->opted_in);
    }

    /**
     * Test 2: Inbound message in Swahili: "BADILI NAMBA +255799887766"
     * Result: Customer address phone is updated in MPM.
     */
    public function testInboundSwahiliCommandSyncsCustomerPhoneInMpm(): void {
        $customer = $this->createCustomer('Amina', 'Salum', self::INITIAL_PHONE);

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
                                    'phone_number_id' => '109876543210',
                                ],
                                'messages' => [
                                    [
                                        'from' => '255712345678',
                                        'id' => 'wamid.HBgLInboundSwahili02',
                                        'timestamp' => '1710000000',
                                        'type' => 'text',
                                        'text' => [
                                            'body' => 'BADILI NAMBA '.self::NEW_PHONE_2,
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

        $updatedAddress = Address::query()->where('owner_id', $customer->id)->first();
        $this->assertNotNull($updatedAddress);
        $this->assertSame(self::NEW_PHONE_2, (string) $updatedAddress->phone);
    }

    /**
     * Test 3: Meta System Webhook event: customer changed number on WhatsApp.
     * Result: Customer address phone is automatically updated in MPM.
     */
    public function testMetaSystemCustomerChangedNumberWebhookSyncsPhone(): void {
        $customer = $this->createCustomer('Kassim', 'Majaliwa', self::INITIAL_PHONE);

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
                                    'phone_number_id' => '109876543210',
                                ],
                                'messages' => [
                                    [
                                        'from' => '255712345678',
                                        'id' => 'wamid.HBgLSystemChange03',
                                        'timestamp' => '1710000000',
                                        'type' => 'system',
                                        'system' => [
                                            'body' => 'Customer changed number',
                                            'type' => 'customer_changed_number',
                                            'new_wa_id' => '255766554433',
                                            'customer' => '255766554433',
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

        $updatedAddress = Address::query()->where('owner_id', $customer->id)->first();
        $this->assertNotNull($updatedAddress);
        $this->assertSame(self::NEW_PHONE_3, (string) $updatedAddress->phone);
    }

    /**
     * Test 4: AddressObserver syncs WhatsApp opt-in when phone is updated directly in MPM.
     */
    public function testAddressObserverSyncsOptInWhenPhoneUpdatedInMpm(): void {
        $customer = $this->createCustomer('Zuhura', 'Hassan', self::INITIAL_PHONE);

        /** @var Address $address */
        $address = Address::query()->where('owner_id', $customer->id)->first();
        $this->assertNotNull($address);
        $address->update(['phone' => '+255711223344']);

        $optIn = WhatsappBusinessOptIn::query()->where('person_id', $customer->id)->first();
        $this->assertNotNull($optIn);
        $this->assertTrue($optIn->opted_in);
    }

    /**
     * Test 5: Dedicated REST API endpoint /api/whatsapp-business-api/sync-number.
     */
    public function testDedicatedSyncNumberApiEndpoint(): void {
        $user = UserFactory::new()->create(['company_id' => $this->companyId]);
        $customer = $this->createCustomer('Devota', 'Mollel', self::INITIAL_PHONE);

        $response = $this->actingAs($user)->postJson('/api/whatsapp-business-api/sync-number', [
            'person_id' => $customer->id,
            'new_phone' => '+255755443322',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.person_id', $customer->id);
        $response->assertJsonPath('data.phone', '+255755443322');
        $response->assertJsonPath('data.opted_in', true);

        $updatedAddress = Address::query()->where('owner_id', $customer->id)->first();
        $this->assertNotNull($updatedAddress);
        $this->assertSame('+255755443322', (string) $updatedAddress->phone);
    }

    /**
     * Test 6: Asynchronous Delivery Webhook Status Failure -> Automatic Fallback to Normal SMS Gateway.
     */
    public function testWebhookDeliveryStatusFailedFallsBackToNormalSmsGateway(): void {
        $wamid = 'wamid.HBgLFailedDeliver100';

        // Pre-create an outgoing WhatsApp SMS record that Meta initially accepted
        $sms = Sms::query()->create([
            'receiver' => self::INITIAL_PHONE,
            'body' => 'Token code: 9988-7766',
            'direction' => Sms::DIRECTION_OUTGOING,
            'status' => Sms::STATUS_SENT,
            'gateway_id' => MpmPlugin::WHATSAPP_BUSINESS_API,
            'uuid' => $wamid,
        ]);

        // Mock Africa's Talking fallback gateway
        $africasTalkingMock = $this->createMock(AfricasTalkingGateway::class);
        $africasTalkingMock->expects($this->once())
            ->method('sendSms');
        $this->app->instance(AfricasTalkingGateway::class, $africasTalkingMock);

        // Mock resolver to supply Africa's Talking as fallback
        $resolverMock = $this->createMock(SmsGatewayResolverService::class);
        $resolverMock->expects($this->once())
            ->method('getFallbackSmsGateway')
            ->willReturn(SmsGatewayResolverService::AFRICAS_TALKING_GATEWAY);

        $resolverMock->expects($this->once())
            ->method('resolveGatewayAndArgs')
            ->willReturnCallback(function (string $gw, Sms $s, array $params) use ($africasTalkingMock) {
                return [
                    'gateway' => $africasTalkingMock,
                    'args' => [$params['body'], $params['receiver'], $s],
                    'gatewayId' => MpmPlugin::AFRICAS_TALKING,
                ];
            });

        $this->app->instance(SmsGatewayResolverService::class, $resolverMock);

        // Incoming Meta webhook delivery status: failed (#131026: Receiver is incapable/offline)
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
                                    'phone_number_id' => '109876543210',
                                ],
                                'statuses' => [
                                    [
                                        'id' => $wamid,
                                        'status' => 'failed',
                                        'timestamp' => '1710000000',
                                        'recipient_id' => '255712345678',
                                        'errors' => [
                                            [
                                                'code' => 131026,
                                                'title' => 'Receiver is incapable of receiving this message',
                                                'message' => 'Receiver is incapable of receiving this message',
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

        // Verify SMS was updated and dispatched via fallback normal SMS gateway
        $updatedSms = Sms::query()->where('uuid', $wamid)->first();
        $this->assertNotNull($updatedSms);
        $this->assertSame(MpmPlugin::AFRICAS_TALKING, $updatedSms->gateway_id);
    }
}
