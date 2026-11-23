<?php

namespace Tests\Feature;

use App\Console\Commands\ResendRejectedMessages;
use App\Jobs\SendBySmsGateway;
use App\Jobs\SmsProcessor;
use App\Models\MainSettings;
use App\Models\MpmPlugin;
use App\Models\Plugins;
use App\Models\Sms;
use App\Models\SmsAttempt;
use App\Plugins\TextbeeSmsGateway\Exceptions\MessageNotSentException;
use App\Plugins\TextbeeSmsGateway\TextbeeSmsGateway;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Services\SmsGatewayResolverService;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Database\Factories\UserFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class WhatsappRoutingTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string RECEIVER = '+255712345678';
    private const string MESSAGE = 'Your token is 1234';
    private const string MESSAGES_URL = 'https://graph.facebook.com/v26.0/1234567890/messages';
    private const string WAMID = 'wamid.HBgMMjU1NzEyMzQ1Njc4FQIAERgSQ0E';

    protected function setUp(): void {
        parent::setUp();

        // AbstractJob resolves the tenant it belongs to from the first user on record.
        UserFactory::new()->create(['company_id' => $this->companyId]);
        MainSettings::query()->firstOrFail()->update(['sms_gateway_id' => MpmPlugin::TEXTBEE_SMS_GATEWAY]);
    }

    public function testOptedInReceiverIsSentByWhatsappOnly(): void {
        $this->enableWhatsapp();
        $this->createContact(lastInboundAt: Carbon::now()->subHour());
        Http::fake([self::MESSAGES_URL => Http::response(['messages' => [['id' => self::WAMID]]])]);
        $this->expectSmsGatewayCalls(0);

        $this->dispatchedSmsProcessor()->handle();

        $sms = $this->sms();
        $this->assertSame(Sms::STATUS_SENT, $sms->status);
        $this->assertSame(MpmPlugin::WHATSAPP_MESSAGING, $sms->gateway_id);
        $this->assertSame(0, $sms->attempts);
        $this->assertDatabaseHas('sms_attempts', [
            'sms_id' => $sms->id,
            'gateway_id' => MpmPlugin::WHATSAPP_MESSAGING,
            'external_id' => self::WAMID,
            'message_type' => SmsAttempt::MESSAGE_TYPE_TEXT,
            'status' => SmsAttempt::STATUS_ACCEPTED,
        ], 'tenant');
        Http::assertSent(fn (Request $request): bool => $request['to'] === '255712345678' && $request['text']['body'] === self::MESSAGE);
    }

    public function testReceiverWithoutOptInIsSentBySms(): void {
        $this->enableWhatsapp();
        $this->createContact(lastInboundAt: Carbon::now()->subHour(), optInStatus: WhatsappContact::OPT_IN_STATUS_OPTED_OUT);
        Http::fake();
        $this->expectSmsGatewayCalls(1);

        $this->dispatchedSmsProcessor()->handle();

        $sms = $this->sms();
        $this->assertSame(Sms::STATUS_SENT, $sms->status);
        $this->assertSame(MpmPlugin::TEXTBEE_SMS_GATEWAY, $sms->gateway_id);
        $this->assertSame(1, $sms->attempts);
        $this->assertSame([MpmPlugin::TEXTBEE_SMS_GATEWAY], $this->attemptGateways());
        Http::assertNothingSent();
    }

    public function testWhatsappErrorHandsTheMessageToTheSmsGatewayAtOnce(): void {
        $this->enableWhatsapp();
        $this->createContact(lastInboundAt: Carbon::now()->subHour());
        $this->fakeMetaError(131026, 'Message undeliverable');
        $this->expectSmsGatewayCalls(1);

        $this->dispatchedSmsProcessor()->handle();

        $this->assertSame(Sms::STATUS_SENDING_BY_SMS, $this->sms()->status);
        $this->queuedSmsFallback()->handle();

        $sms = $this->sms();
        $this->assertSame(Sms::STATUS_SENT, $sms->status);
        $this->assertSame(MpmPlugin::TEXTBEE_SMS_GATEWAY, $sms->gateway_id);
        $this->assertSame([MpmPlugin::WHATSAPP_MESSAGING, MpmPlugin::TEXTBEE_SMS_GATEWAY], $this->attemptGateways());
        $this->assertSame(131026, SmsAttempt::query()->where('gateway_id', MpmPlugin::WHATSAPP_MESSAGING)->sole()->error_code);
    }

    public function testATemporaryWhatsappErrorIsNotRetriedWhenAnSmsGatewayCanSendNow(): void {
        $this->enableWhatsapp();
        $this->createContact(lastInboundAt: Carbon::now()->subHour());
        $this->fakeMetaError(131000, 'Something went wrong');
        $this->expectSmsGatewayCalls(1);

        $this->dispatchedSmsProcessor()->handle();
        $this->queuedSmsFallback()->handle();

        $this->assertSame(MpmPlugin::TEXTBEE_SMS_GATEWAY, $this->sms()->gateway_id);
        Http::assertSentCount(1);
    }

    public function testAFailedSmsFallbackWaitsForItsOneResend(): void {
        $this->enableWhatsapp();
        $this->createContact(lastInboundAt: Carbon::now()->subHour());
        $this->fakeMetaError(131026, 'Message undeliverable');
        $this->mock(TextbeeSmsGateway::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendSms')->twice()->andThrow(new MessageNotSentException('TextBee is down'));
        });

        $this->dispatchedSmsProcessor()->handle();
        $this->queuedSmsFallback()->handle();

        $sms = $this->sms();
        $this->assertSame(Sms::STATUS_WAITING_FOR_RETRY, $sms->status);
        $this->assertSame(MpmPlugin::TEXTBEE_SMS_GATEWAY, $sms->gateway_id);
        $this->assertSame(1, $sms->attempts);

        $this->runResendCommand();

        $sms = $this->sms();
        $this->assertSame(Sms::STATUS_FAILED, $sms->status);
        $this->assertSame('TextBee is down', $sms->error_message);
        $this->assertSame(2, $sms->attempts);
    }

    public function testWithoutAnSmsGatewayATemporaryErrorWaitsForOneWhatsappRetry(): void {
        $this->withoutSmsGateway();
        $this->enableWhatsapp();
        $this->createContact(lastInboundAt: Carbon::now()->subHour());
        Http::fake([self::MESSAGES_URL => Http::sequence()
            ->push(['error' => ['message' => 'Something went wrong', 'code' => 131000]], 500)
            ->push(['messages' => [['id' => self::WAMID]]])]);

        $this->dispatchedSmsProcessor()->handle();

        $sms = $this->sms();
        $this->assertSame(Sms::STATUS_WAITING_FOR_RETRY, $sms->status);
        $this->assertSame(MpmPlugin::WHATSAPP_MESSAGING, $sms->gateway_id);

        $this->runResendCommand();

        $this->assertSame(Sms::STATUS_SENT, $this->sms()->status);
        $this->assertSame([SmsAttempt::STATUS_FAILED, SmsAttempt::STATUS_ACCEPTED], SmsAttempt::query()->orderBy('id')->pluck('status')->all());
        // The retry sends what the first try sent, because the body parser values are gone by then. MySQL's JSON column reorders keys, so only the content is compared.
        $requests = Http::recorded()->map(fn (array $exchange): array => $exchange[0]->data())->all();
        $this->assertEquals($requests[0], $requests[1]);
    }

    public function testWithoutAnSmsGatewayARetryThatFailsAgainIsFinal(): void {
        $this->withoutSmsGateway();
        $this->enableWhatsapp();
        $this->createContact(lastInboundAt: Carbon::now()->subHour());
        $this->fakeMetaError(131000, 'Something went wrong');

        $this->dispatchedSmsProcessor()->handle();
        $this->runResendCommand();
        $this->runResendCommand();

        $this->assertSame(Sms::STATUS_FAILED, $this->sms()->status);
        Http::assertSentCount(2);
    }

    public function testWithoutAnSmsGatewayAPermanentErrorIsNotRetried(): void {
        $this->withoutSmsGateway();
        $this->enableWhatsapp();
        $this->createContact(lastInboundAt: Carbon::now()->subHour());
        $this->fakeMetaError(131026, 'Message undeliverable');

        $this->dispatchedSmsProcessor()->handle();
        $this->runResendCommand();

        $sms = $this->sms();
        $this->assertSame(Sms::STATUS_FAILED, $sms->status);
        $this->assertStringContainsString('Message undeliverable', (string) $sms->error_message);
        Http::assertSentCount(1);
    }

    public function testWithoutAnSmsGatewayARetryAfterOptOutIsFinal(): void {
        $this->withoutSmsGateway();
        $this->enableWhatsapp();
        $contact = $this->createContact(lastInboundAt: Carbon::now()->subHour());
        $this->fakeMetaError(131000, 'Something went wrong');

        $this->dispatchedSmsProcessor()->handle();
        $contact->update(['opt_in_status' => WhatsappContact::OPT_IN_STATUS_OPTED_OUT]);
        $this->runResendCommand();

        $this->assertSame(Sms::STATUS_FAILED, $this->sms()->status);
        Http::assertSentCount(1);
    }

    public function testMissingTemplateOutsideTheWindowFallsBackToSms(): void {
        $this->enableWhatsapp();
        // A manual SMS is not an MPM notification, so outside the window there is no template to send.
        $this->createContact(lastInboundAt: Carbon::now()->subDays(2));
        Http::fake();
        $this->expectSmsGatewayCalls(1);

        $this->dispatchedSmsProcessor()->handle();
        $this->queuedSmsFallback()->handle();

        $sms = $this->sms();
        $this->assertSame(MpmPlugin::TEXTBEE_SMS_GATEWAY, $sms->gateway_id);
        $attempt = SmsAttempt::query()->where('gateway_id', MpmPlugin::WHATSAPP_MESSAGING)->sole();
        $this->assertSame(SmsAttempt::STATUS_FAILED, $attempt->status);
        $this->assertSame(SmsAttempt::MESSAGE_TYPE_TEMPLATE, $attempt->message_type);
        $this->assertNotNull($attempt->failed_at);
        Http::assertNothingSent();
    }

    public function testInactivePluginLeavesSmsUnchanged(): void {
        $this->enableWhatsapp(pluginStatus: Plugins::INACTIVE);
        $this->createContact(lastInboundAt: Carbon::now()->subHour());
        Http::fake();
        $this->expectSmsGatewayCalls(1);

        $this->dispatchedSmsProcessor()->handle();

        $this->assertSame(MpmPlugin::TEXTBEE_SMS_GATEWAY, $this->sms()->gateway_id);
        $this->assertSame([MpmPlugin::TEXTBEE_SMS_GATEWAY], $this->attemptGateways());
        Http::assertNothingSent();
    }

    public function testWhatsappDoesNotCountAsAnSmsGateway(): void {
        $this->withoutSmsGateway();
        $this->enableWhatsapp();

        $this->assertFalse(resolve(SmsGatewayResolverService::class)->isSmsGatewayConfigured());
    }

    private function withoutSmsGateway(): void {
        MainSettings::query()->firstOrFail()->update(['sms_gateway_id' => null]);
    }

    private function enableWhatsapp(int $pluginStatus = Plugins::ACTIVE): void {
        Plugins::query()->create([
            'mpm_plugin_id' => MpmPlugin::WHATSAPP_MESSAGING,
            'status' => $pluginStatus,
        ]);
        WhatsappCredential::query()->create([
            'access_token' => Crypt::encryptString('test-access-token'),
            'app_secret' => Crypt::encryptString('test-app-secret'),
            'webhook_verify_token' => Crypt::encryptString('test-verify-token'),
            'phone_number_id' => '1234567890',
        ]);
    }

    private function createContact(Carbon $lastInboundAt, string $optInStatus = WhatsappContact::OPT_IN_STATUS_OPTED_IN): WhatsappContact {
        return WhatsappContact::query()->create([
            'phone' => self::RECEIVER,
            'wa_id' => '255712345678',
            'opt_in_status' => $optInStatus,
            'last_inbound_at' => $lastInboundAt,
        ]);
    }

    private function fakeMetaError(int $code, string $message): void {
        Http::fake([self::MESSAGES_URL => Http::response([
            'error' => ['message' => $message, 'type' => 'OAuthException', 'code' => $code, 'error_data' => ['details' => $message], 'fbtrace_id' => 'trace'],
        ], 400)]);
    }

    private function expectSmsGatewayCalls(int $times): void {
        $this->mock(TextbeeSmsGateway::class, function (MockInterface $mock) use ($times): void {
            $mock->shouldReceive('sendSms')->times($times);
        });
    }

    private function sms(): Sms {
        return Sms::query()->where('receiver', self::RECEIVER)->sole();
    }

    /** @return list<int> */
    private function attemptGateways(): array {
        return SmsAttempt::query()->orderBy('id')->pluck('gateway_id')->all();
    }

    private function queuedSmsFallback(): SendBySmsGateway {
        $job = Queue::pushed(SendBySmsGateway::class)->first();

        if (!$job instanceof SendBySmsGateway) {
            $this->fail('A WhatsApp failure with an SMS gateway configured should queue SendBySmsGateway.');
        }

        return $job;
    }

    /**
     * The console kernel instantiates commands before the test can bind mocks,
     * so the command is resolved fresh here to pick up the mocked services.
     */
    private function runResendCommand(): void {
        $command = $this->app->make(ResendRejectedMessages::class);
        $command->setLaravel($this->app);
        $command->run(new ArrayInput(['amount' => '5', '--company-id' => (string) $this->companyId]), new BufferedOutput());
    }

    private function dispatchedSmsProcessor(): SmsProcessor {
        Queue::fake();

        app()->make(SmsService::class)->sendSms(
            ['message' => self::MESSAGE, 'phone' => self::RECEIVER],
            SmsTypes::MANUAL_SMS,
            SmsConfigs::class
        );

        $job = Queue::pushed(SmsProcessor::class)->first();

        if (!$job instanceof SmsProcessor) {
            $this->fail('Sending an SMS should queue an SmsProcessor.');
        }

        return $job;
    }
}
