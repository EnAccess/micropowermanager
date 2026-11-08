<?php

namespace Tests\Feature;

use App\Jobs\SendBySmsGateway;
use App\Jobs\SmsProcessor;
use App\Models\MainSettings;
use App\Models\MpmPlugin;
use App\Models\Plugins;
use App\Models\Sms;
use App\Models\User;
use App\Plugins\TextbeeSmsGateway\TextbeeSmsGateway;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Plugins\WhatsappMessaging\Models\WhatsappTemplate;
use Database\Factories\Address\AddressFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\UserFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class ManualWhatsappTemplateMessageTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string RECEIVER = '+255712345678';
    private const string MESSAGES_URL = 'https://graph.facebook.com/v26.0/1234567890/messages';
    private const string TEMPLATE_ID = '594425479261596';

    private User $user;

    protected function setUp(): void {
        parent::setUp();

        // AbstractJob resolves the tenant it belongs to from the first user on record.
        $this->user = UserFactory::new()->create(['company_id' => $this->companyId]);
        MainSettings::query()->firstOrFail()->update(['sms_gateway_id' => MpmPlugin::TEXTBEE_SMS_GATEWAY]);
        Plugins::query()->create(['mpm_plugin_id' => MpmPlugin::WHATSAPP_MESSAGING, 'status' => Plugins::ACTIVE]);
        WhatsappCredential::query()->create([
            'access_token' => Crypt::encryptString('test-access-token'),
            'app_secret' => Crypt::encryptString('test-app-secret'),
            'webhook_verify_token' => Crypt::encryptString('test-verify-token'),
            'phone_number_id' => '1234567890',
        ]);
        $person = PersonFactory::new()->create(['name' => 'Amina', 'surname' => 'Juma']);
        $address = AddressFactory::new()->make(['phone' => self::RECEIVER, 'is_primary' => 1]);
        $address->owner()->associate($person);
        $address->save();
        WhatsappTemplate::query()->create([
            'meta_template_id' => self::TEMPLATE_ID,
            'name' => 'outage_notice',
            'language' => 'en',
            'category' => 'UTILITY',
            'status' => WhatsappTemplate::STATUS_APPROVED,
            'parameter_format' => WhatsappTemplate::PARAMETER_FORMAT_NAMED,
            'components' => [['type' => 'BODY', 'text' => 'Dear {{name}}, power in {{area}} is off until {{time}}.']],
        ]);
        Queue::fake();
    }

    public function testAnOptedInCustomerGetsTheTemplateWithTheirNameFilledIn(): void {
        $this->optIn();
        Http::fake([self::MESSAGES_URL => Http::response(['messages' => [['id' => 'wamid.1']]])]);
        $this->expectSmsGatewayCalls(0);

        $this->sendTemplate()->assertCreated();
        $this->runQueuedSmsProcessor();

        $sms = Sms::query()->sole();
        $this->assertSame('Dear Amina, power in Kigali is off until 18:00.', $sms->body);
        $this->assertSame(MpmPlugin::WHATSAPP_MESSAGING, $sms->gateway_id);
        Http::assertSent(fn (Request $request): bool => $request['template']['name'] === 'outage_notice'
            && array_column($request['template']['components'][0]['parameters'], 'text', 'parameter_name') === ['name' => 'Amina', 'area' => 'Kigali', 'time' => '18:00']);
    }

    public function testACustomerWhoIsNotOptedInGetsTheSameWordsBySms(): void {
        Http::fake();
        $this->mock(TextbeeSmsGateway::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendSms')->once()->withArgs(fn (string $body): bool => $body === 'Dear Amina, power in Kigali is off until 18:00.');
        });

        $this->sendTemplate()->assertCreated();
        $this->runQueuedSmsProcessor();

        $this->assertSame(MpmPlugin::TEXTBEE_SMS_GATEWAY, Sms::query()->sole()->gateway_id);
        Http::assertNothingSent();
    }

    public function testAFailedTemplateSendFallsBackToSms(): void {
        $this->optIn();
        Http::fake([self::MESSAGES_URL => Http::response(['error' => ['message' => 'Message undeliverable', 'code' => 131026]], 400)]);
        $this->expectSmsGatewayCalls(1);

        $this->sendTemplate()->assertCreated();
        $this->runQueuedSmsProcessor();
        Queue::pushed(SendBySmsGateway::class)->first()->handle();

        $this->assertSame(MpmPlugin::TEXTBEE_SMS_GATEWAY, Sms::query()->sole()->gateway_id);
    }

    public function testANotificationTemplateCannotBeSentByHand(): void {
        WhatsappTemplate::query()->where('meta_template_id', self::TEMPLATE_ID)->update(['name' => 'token_confirmation_meter']);

        $this->sendTemplate()->assertStatus(422)->assertJsonPath('message', 'The WhatsApp template "token_confirmation_meter" cannot be used for manual messages.');

        $this->assertSame(0, Sms::query()->count());
    }

    public function testFreeTextNeedsAnSmsGatewayButATemplateDoesNot(): void {
        MainSettings::query()->firstOrFail()->update(['sms_gateway_id' => null]);

        $this->actingAs($this->user)->postJson('/api/sms/storeandsend', ['phone' => self::RECEIVER, 'message' => 'Power is off'])
            ->assertStatus(422)
            ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'wrote in the last 24 hours'));
        $this->sendTemplate()->assertCreated();
    }

    public function testWithoutAnSmsGatewayFreeTextReachesACustomerWhoseWindowIsOpen(): void {
        MainSettings::query()->firstOrFail()->update(['sms_gateway_id' => null]);
        WhatsappContact::query()->create(['phone' => self::RECEIVER, 'opt_in_status' => WhatsappContact::OPT_IN_STATUS_OPTED_IN, 'last_inbound_at' => Carbon::now()->subHour()]);

        $this->actingAs($this->user)->postJson('/api/sms/storeandsend', ['phone' => self::RECEIVER, 'message' => 'Power is off'])->assertCreated();
    }

    public function testWithoutAnSmsGatewayBulkFreeTextIsStillRefused(): void {
        MainSettings::query()->firstOrFail()->update(['sms_gateway_id' => null]);
        WhatsappContact::query()->create(['phone' => self::RECEIVER, 'opt_in_status' => WhatsappContact::OPT_IN_STATUS_OPTED_IN, 'last_inbound_at' => Carbon::now()->subHour()]);

        $this->actingAs($this->user)->postJson('/api/sms/bulk', ['type' => 'person', 'receivers' => [self::RECEIVER], 'message' => 'Power is off'])->assertStatus(422);
    }

    public function testBulkSendFillsTheTemplateForEachReceiver(): void {
        $this->actingAs($this->user)->postJson('/api/sms/bulk', [
            'type' => 'person',
            'receivers' => [self::RECEIVER],
            'template_id' => self::TEMPLATE_ID,
            'template_values' => ['area' => 'Kigali', 'time' => '18:00'],
        ])->assertOk()->assertJsonPath('data.queued', 1);

        $this->assertSame('Dear Amina, power in Kigali is off until 18:00.', Sms::query()->sole()->body);
    }

    private function sendTemplate(): TestResponse {
        return $this->actingAs($this->user)->postJson('/api/sms/storeandsend', [
            'phone' => self::RECEIVER,
            'template_id' => self::TEMPLATE_ID,
            'template_values' => ['area' => 'Kigali', 'time' => '18:00'],
        ]);
    }

    private function optIn(): void {
        WhatsappContact::query()->create([
            'phone' => self::RECEIVER,
            'opt_in_status' => WhatsappContact::OPT_IN_STATUS_OPTED_IN,
            'last_inbound_at' => Carbon::now()->subDays(2),
        ]);
    }

    private function expectSmsGatewayCalls(int $times): void {
        $this->mock(TextbeeSmsGateway::class, function (MockInterface $mock) use ($times): void {
            $mock->shouldReceive('sendSms')->times($times);
        });
    }

    private function runQueuedSmsProcessor(): void {
        $job = Queue::pushed(SmsProcessor::class)->first();

        if (!$job instanceof SmsProcessor) {
            $this->fail('Sending a message should queue an SmsProcessor.');
        }

        $job->handle();
    }
}
