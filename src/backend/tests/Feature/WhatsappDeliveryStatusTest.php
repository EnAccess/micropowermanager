<?php

namespace Tests\Feature;

use App\Console\Commands\ResendRejectedMessages;
use App\Models\MainSettings;
use App\Models\MpmPlugin;
use App\Models\Sms;
use App\Plugins\TextbeeSmsGateway\TextbeeSmsGateway;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Plugins\WhatsappMessaging\Models\WhatsappMessage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class WhatsappDeliveryStatusTest extends TestCase {
    private const string APP_SECRET = 'test-app-secret';
    private const string PHONE_NUMBER_ID = '106540352242922';
    private const string WAMID = 'wamid.HBgMMjU1NzEyMzQ1Njc4FQIAERgSQ0E';
    private const int SENT_AT = 1750263700;
    private const int DELIVERED_AT = 1750263773;
    private const int READ_AT = 1750263800;

    private Sms $sms;
    private WhatsappMessage $message;

    protected function setUp(): void {
        parent::setUp();

        MainSettings::query()->firstOrFail()->update(['sms_gateway_id' => MpmPlugin::TEXTBEE_SMS_GATEWAY]);

        $this->sms = Sms::query()->create([
            'receiver' => '+255712345678',
            'body' => 'Your token is 1234',
            'direction' => Sms::DIRECTION_OUTGOING,
            'status' => Sms::STATUS_SENT,
            'gateway_id' => MpmPlugin::WHATSAPP_MESSAGING,
            'attempts' => 1,
        ]);
        $this->message = WhatsappMessage::query()->create([
            'sms_id' => $this->sms->id,
            'wamid' => self::WAMID,
            'message_type' => WhatsappMessage::MESSAGE_TYPE_TEMPLATE,
            'template_name' => 'mpm_payment_receipt_meter',
            'template_language' => 'en',
            'status' => WhatsappMessage::STATUS_ACCEPTED,
        ]);
    }

    public function testStatusesMoveFromSentToDeliveredToRead(): void {
        $this->createCredential();

        $this->postStatus('sent', self::SENT_AT)->assertOk();
        $this->assertMessageStatus(WhatsappMessage::STATUS_SENT, Sms::STATUS_SENT);
        $this->assertSame(self::SENT_AT, $this->message->refresh()->sent_at?->getTimestamp());

        $this->postStatus('delivered', self::DELIVERED_AT)->assertOk();
        $this->assertMessageStatus(WhatsappMessage::STATUS_DELIVERED, Sms::STATUS_DELIVERED);
        $this->assertSame(self::DELIVERED_AT, $this->message->refresh()->delivered_at?->getTimestamp());

        $this->postStatus('read', self::READ_AT)->assertOk();
        $this->assertMessageStatus(WhatsappMessage::STATUS_READ, Sms::STATUS_DELIVERED);
        $this->assertSame(self::READ_AT, $this->message->refresh()->read_at?->getTimestamp());
        $this->assertSame('utility', $this->message->pricing_category);
    }

    public function testReadBeforeDeliveredEndsAtRead(): void {
        $this->createCredential();

        $this->postStatus('read', self::READ_AT)->assertOk();
        $this->postStatus('delivered', self::DELIVERED_AT)->assertOk();

        $this->assertMessageStatus(WhatsappMessage::STATUS_READ, Sms::STATUS_DELIVERED);
    }

    public function testFailureAfterDeliveryIsIgnored(): void {
        $this->createCredential();

        $this->postStatus('delivered', self::DELIVERED_AT)->assertOk();
        $this->postStatus('failed', self::READ_AT)->assertOk();

        $this->assertMessageStatus(WhatsappMessage::STATUS_DELIVERED, Sms::STATUS_DELIVERED);
    }

    public function testRepeatedFailureIsResentBySmsOnlyOnce(): void {
        $this->createCredential();
        $this->mock(TextbeeSmsGateway::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendSms')->once();
        });

        $this->postStatus('failed', self::DELIVERED_AT)->assertOk();

        $this->assertMessageStatus(WhatsappMessage::STATUS_FAILED, Sms::STATUS_FAILED);
        $this->assertSame(131026, $this->message->error_code);
        $this->assertSame(self::DELIVERED_AT, $this->message->failed_at?->getTimestamp());
        $this->assertSame('WhatsApp: Message undeliverable (131026)', $this->sms->refresh()->error_message);

        $this->runResendRejected();

        $this->sms->refresh();
        $this->assertSame(Sms::STATUS_SENT, $this->sms->status);
        $this->assertSame(MpmPlugin::TEXTBEE_SMS_GATEWAY, $this->sms->gateway_id);

        $this->postStatus('failed', self::DELIVERED_AT)->assertOk();
        $this->runResendRejected();

        $this->assertSame(Sms::STATUS_SENT, $this->sms->refresh()->status);
    }

    public function testFailureWithFallbackDisabledIsNotRetried(): void {
        $this->createCredential(smsFallbackEnabled: false);
        $this->mock(TextbeeSmsGateway::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('sendSms');
        });

        $this->postStatus('failed', self::DELIVERED_AT)->assertOk();
        $this->runResendRejected();

        $this->sms->refresh();
        $this->assertSame(Sms::STATUS_FAILED, $this->sms->status);
        $this->assertSame(ResendRejectedMessages::MAX_ATTEMPTS, $this->sms->attempts);
    }

    private function createCredential(bool $smsFallbackEnabled = true): void {
        WhatsappCredential::query()->create([
            'access_token' => Crypt::encryptString('test-access-token'),
            'app_secret' => Crypt::encryptString(self::APP_SECRET),
            'webhook_verify_token' => Crypt::encryptString('test-verify-token'),
            'phone_number_id' => self::PHONE_NUMBER_ID,
            'sms_fallback_enabled' => $smsFallbackEnabled,
        ]);
    }

    private function assertMessageStatus(string $whatsappStatus, int $smsStatus): void {
        $this->assertSame($whatsappStatus, $this->message->refresh()->status);
        $this->assertSame($smsStatus, $this->sms->refresh()->status);
    }

    private function postStatus(string $status, int $timestamp): TestResponse {
        $item = [
            'id' => self::WAMID,
            'status' => $status,
            'timestamp' => (string) $timestamp,
            'recipient_id' => '255712345678',
            'pricing' => ['billable' => true, 'pricing_model' => 'PMP', 'category' => 'utility'],
        ];

        if ($status === 'failed') {
            $item['errors'] = [[
                'code' => 131026,
                'title' => 'Message undeliverable',
                'message' => 'Message undeliverable',
                'error_data' => ['details' => 'Message Undeliverable.'],
            ]];
        }

        $body = (string) json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => '123456789',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => ['display_phone_number' => '15550783881', 'phone_number_id' => self::PHONE_NUMBER_ID],
                        'statuses' => [$item],
                    ],
                ]],
            ]],
        ]);

        return $this->call('POST', '/api/whatsapp-messaging/webhook/'.$this->companyId, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, self::APP_SECRET),
        ], $body);
    }

    /**
     * The console kernel instantiates commands before the test can bind mocks,
     * so the command is resolved fresh here to pick up the mocked gateway.
     */
    private function runResendRejected(): void {
        $command = $this->app->make(ResendRejectedMessages::class);
        $command->setLaravel($this->app);
        $command->run(new ArrayInput(['amount' => '5', '--company-id' => (string) $this->companyId]), new BufferedOutput());
    }
}
