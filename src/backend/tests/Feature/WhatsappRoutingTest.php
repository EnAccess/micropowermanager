<?php

namespace Tests\Feature;

use App\Jobs\SmsProcessor;
use App\Models\MainSettings;
use App\Models\MpmPlugin;
use App\Models\Plugins;
use App\Models\Sms;
use App\Plugins\TextbeeSmsGateway\Exceptions\MessageNotSentException;
use App\Plugins\TextbeeSmsGateway\TextbeeSmsGateway;
use App\Plugins\WhatsappMessaging\Jobs\RetryWhatsappMessage;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Plugins\WhatsappMessaging\Models\WhatsappMessage;
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
        $contact = $this->createContact(lastInboundAt: Carbon::now()->subHour());
        Http::fake([self::MESSAGES_URL => Http::response(['messages' => [['id' => self::WAMID]]])]);
        $this->expectSmsGatewayCalls(0);

        $this->dispatchedSmsProcessor()->handle();

        $sms = $this->sms();
        $this->assertSame(Sms::STATUS_SENT, $sms->status);
        $this->assertSame(MpmPlugin::WHATSAPP_MESSAGING, $sms->gateway_id);
        $this->assertDatabaseHas('whatsapp_messages', [
            'sms_id' => $sms->id,
            'whatsapp_contact_id' => $contact->id,
            'wamid' => self::WAMID,
            'message_type' => WhatsappMessage::MESSAGE_TYPE_TEXT,
            'status' => WhatsappMessage::STATUS_ACCEPTED,
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
        $this->assertSame(0, WhatsappMessage::query()->count());
        Http::assertNothingSent();
    }

    public function testWhatsappErrorFallsBackToSms(): void {
        $this->enableWhatsapp();
        $this->createContact(lastInboundAt: Carbon::now()->subHour());
        $this->fakeMetaError(131026, 'Message undeliverable');
        $this->expectSmsGatewayCalls(1);

        $this->dispatchedSmsProcessor()->handle();

        $sms = $this->sms();
        $this->assertSame(Sms::STATUS_SENT, $sms->status);
        $this->assertSame(MpmPlugin::TEXTBEE_SMS_GATEWAY, $sms->gateway_id);
        $message = WhatsappMessage::query()->where('sms_id', $sms->id)->sole();
        $this->assertSame(WhatsappMessage::STATUS_FAILED, $message->status);
        $this->assertSame(131026, $message->error_code);
    }

    public function testTemporaryWhatsappErrorIsRetriedInsteadOfSentBySms(): void {
        $this->enableWhatsapp();
        $this->createContact(lastInboundAt: Carbon::now()->subHour());
        $this->fakeMetaError(131000, 'Something went wrong');
        $this->expectSmsGatewayCalls(0);

        $this->freezeTime();

        $this->dispatchedSmsProcessor()->handle();

        $this->assertSame(MpmPlugin::WHATSAPP_MESSAGING, $this->sms()->gateway_id);
        Queue::assertPushed(RetryWhatsappMessage::class, 1);
        $retry = $this->queuedRetry();
        $this->assertSame('sms', $retry->queue);
        $this->assertEquals(Carbon::now()->addMinutes(RetryWhatsappMessage::DELAY_MINUTES), $retry->delay);
    }

    public function testRetryAcceptedByWhatsappSendsNoSms(): void {
        $this->enableWhatsapp();
        $this->createContact(lastInboundAt: Carbon::now()->subHour());
        Http::fake([self::MESSAGES_URL => Http::sequence()
            ->push(['error' => ['message' => 'Something went wrong', 'code' => 131000]], 500)
            ->push(['messages' => [['id' => self::WAMID]]])]);
        $this->expectSmsGatewayCalls(0);

        $this->dispatchedSmsProcessor()->handle();
        $this->queuedRetry()->handle();

        $this->assertSame(MpmPlugin::WHATSAPP_MESSAGING, $this->sms()->gateway_id);
        $this->assertSame([WhatsappMessage::STATUS_FAILED, WhatsappMessage::STATUS_ACCEPTED], WhatsappMessage::query()->orderBy('id')->pluck('status')->all());
    }

    public function testRetryThatFailsAgainIsSentBySms(): void {
        $this->enableWhatsapp();
        $this->createContact(lastInboundAt: Carbon::now()->subHour());
        $this->fakeMetaError(131000, 'Something went wrong');
        $this->expectSmsGatewayCalls(1);

        $this->dispatchedSmsProcessor()->handle();
        $this->queuedRetry()->handle();

        $this->assertSame(MpmPlugin::TEXTBEE_SMS_GATEWAY, $this->sms()->gateway_id);
        Queue::assertPushed(RetryWhatsappMessage::class, 1);
    }

    public function testRetryAfterOptOutIsSentBySms(): void {
        $this->enableWhatsapp();
        $contact = $this->createContact(lastInboundAt: Carbon::now()->subHour());
        $this->fakeMetaError(131000, 'Something went wrong');
        $this->expectSmsGatewayCalls(1);

        $this->dispatchedSmsProcessor()->handle();
        $contact->update(['opt_in_status' => WhatsappContact::OPT_IN_STATUS_OPTED_OUT]);
        $this->queuedRetry()->handle();

        $this->assertSame(MpmPlugin::TEXTBEE_SMS_GATEWAY, $this->sms()->gateway_id);
        Http::assertSentCount(1);
    }

    public function testRetryAfterThePluginIsTurnedOffIsSentBySms(): void {
        $this->enableWhatsapp();
        $this->createContact(lastInboundAt: Carbon::now()->subHour());
        $this->fakeMetaError(131000, 'Something went wrong');
        $this->expectSmsGatewayCalls(1);

        $this->dispatchedSmsProcessor()->handle();
        Plugins::query()->where('mpm_plugin_id', MpmPlugin::WHATSAPP_MESSAGING)->update(['status' => Plugins::INACTIVE]);
        $this->queuedRetry()->handle();

        $this->assertSame(MpmPlugin::TEXTBEE_SMS_GATEWAY, $this->sms()->gateway_id);
        Http::assertSentCount(1);
    }

    public function testRetryWhoseSmsAlsoFailsMarksTheMessageFailed(): void {
        $this->enableWhatsapp();
        $this->createContact(lastInboundAt: Carbon::now()->subHour());
        $this->fakeMetaError(131000, 'Something went wrong');
        $this->mock(TextbeeSmsGateway::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendSms')->once()->andThrow(new MessageNotSentException('TextBee is down'));
        });

        $this->dispatchedSmsProcessor()->handle();
        $retry = $this->queuedRetry();
        // The queue worker calls failed() when handle() throws; the test plays that part.
        try {
            $retry->handle();
            $this->fail('The SMS fallback of the retry should have thrown.');
        } catch (MessageNotSentException $exception) {
            $retry->failed($exception);
        }

        $sms = $this->sms();
        $this->assertSame(Sms::STATUS_FAILED, $sms->status);
        $this->assertSame('TextBee is down', $sms->error_message);
    }

    public function testMissingTemplateOutsideTheWindowFallsBackToSms(): void {
        $this->enableWhatsapp();
        // A manual SMS is not an MPM notification text, so outside the window there is no template to send.
        $this->createContact(lastInboundAt: Carbon::now()->subDays(2));
        Http::fake();
        $this->expectSmsGatewayCalls(1);

        $this->dispatchedSmsProcessor()->handle();

        $sms = $this->sms();
        $this->assertSame(MpmPlugin::TEXTBEE_SMS_GATEWAY, $sms->gateway_id);
        $message = WhatsappMessage::query()->where('sms_id', $sms->id)->sole();
        $this->assertSame(WhatsappMessage::STATUS_FAILED, $message->status);
        $this->assertSame(WhatsappMessage::MESSAGE_TYPE_TEMPLATE, $message->message_type);
        $this->assertNotNull($message->failed_at);
        Http::assertNothingSent();
    }

    public function testInactivePluginLeavesSmsUnchanged(): void {
        $this->enableWhatsapp(pluginStatus: Plugins::INACTIVE);
        $this->createContact(lastInboundAt: Carbon::now()->subHour());
        Http::fake();
        $this->expectSmsGatewayCalls(1);

        $this->dispatchedSmsProcessor()->handle();

        $this->assertSame(MpmPlugin::TEXTBEE_SMS_GATEWAY, $this->sms()->gateway_id);
        $this->assertSame(0, WhatsappMessage::query()->count());
        Http::assertNothingSent();
    }

    public function testWhatsappAloneCountsAsAConfiguredGateway(): void {
        MainSettings::query()->firstOrFail()->update(['sms_gateway_id' => null]);
        $resolver = resolve(SmsGatewayResolverService::class);

        $this->assertFalse($resolver->isSmsGatewayConfigured());

        $this->enableWhatsapp();

        $this->assertTrue($resolver->isSmsGatewayConfigured());
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

    private function queuedRetry(): RetryWhatsappMessage {
        $job = Queue::pushed(RetryWhatsappMessage::class)->first();

        if (!$job instanceof RetryWhatsappMessage) {
            $this->fail('A temporary WhatsApp error should queue a RetryWhatsappMessage.');
        }

        return $job;
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
