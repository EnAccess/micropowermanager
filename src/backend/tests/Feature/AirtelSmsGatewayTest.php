<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SmsProcessor;
use App\Models\MainSettings;
use App\Models\Sms;
use App\Plugins\AirtelSmsGateway\Exceptions\MessageNotSentException;
use App\Plugins\AirtelSmsGateway\Models\AirtelSmsCredential;
use App\Plugins\AirtelSmsGateway\Models\AirtelSmsMessage;
use App\Plugins\AirtelSmsGateway\Providers\AirtelSmsGatewayServiceProvider;
use App\Plugins\AirtelSmsGateway\Services\AirtelSmsCredentialService;
use App\Plugins\AirtelSmsGateway\Smpp\SmppTransmitter;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class AirtelSmsGatewayTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string RECEIVER = '+255712345678';
    private const string MESSAGE = 'Your token is 1234';
    private const array CREDENTIALS = [
        'host' => 'smsc.test',
        'port' => 2775,
        'system_id' => 'system',
        'password' => 'secret',
        'source_address' => 'OPERATOR',
    ];

    protected function setUp(): void {
        parent::setUp();

        // AbstractJob resolves the tenant it belongs to from the first user on record.
        UserFactory::new()->create(['company_id' => $this->companyId]);
        MainSettings::query()->firstOrFail()->update(['sms_gateway_plugin_slug' => AirtelSmsGatewayServiceProvider::SLUG]);
    }

    public function testAnSmsIsSubmittedOverSmppAndMarkedAsSent(): void {
        $this->saveCredentials();
        $this->mock(SmppTransmitter::class, function (MockInterface $mock): void {
            $mock->shouldReceive('send')
                ->once()
                ->with('smsc.test', 2775, 'system', 'secret', 'OPERATOR', '255712345678', self::MESSAGE)
                ->andReturn('smsc-message-1');
        });

        $this->dispatchedSmsProcessor()->handle();

        $sms = Sms::query()->where('receiver', self::RECEIVER)->firstOrFail();
        $this->assertSame(Sms::STATUS_SENT, $sms->status);
        $this->assertSame(AirtelSmsGatewayServiceProvider::SLUG, $sms->sms_gateway_plugin_slug);
        $this->assertSame('smsc-message-1', AirtelSmsMessage::query()->where('sms_id', $sms->id)->firstOrFail()->message_id);
    }

    public function testAFailedSubmissionIsMarkedAsFailedWithTheReason(): void {
        $this->saveCredentials();
        $reason = 'The SMSC rejected the request: Invalid password (status 0x0000000E)';
        $this->mock(SmppTransmitter::class, function (MockInterface $mock) use ($reason): void {
            $mock->shouldReceive('send')->andThrow(new MessageNotSentException($reason));
        });

        $this->assertSmsFailsWith($reason);
    }

    public function testNoConnectionIsOpenedWhenCredentialsAreMissing(): void {
        $this->mock(SmppTransmitter::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('send');
        });

        $this->assertSmsFailsWith('Airtel SMS credentials are not configured.');
    }

    public function testCredentialsAreStoredWithAnEncryptedPassword(): void {
        $response = $this->actingAs(UserFactory::new()->create())
            ->putJson('/api/airtel-sms-gateway/credential', self::CREDENTIALS);

        $response->assertOk();
        $response->assertJsonPath('data.host', 'smsc.test');
        $response->assertJsonPath('data.port', 2775);
        $response->assertJsonPath('data.source_address', 'OPERATOR');
        $this->assertNotSame('secret', AirtelSmsCredential::query()->firstOrFail()->password);
        $this->assertSame('secret', $this->app->make(AirtelSmsCredentialService::class)->getCredentials()?->password);
    }

    public function testCredentialsWithAnInvalidPortAreRejected(): void {
        $response = $this->actingAs(UserFactory::new()->create())
            ->putJson('/api/airtel-sms-gateway/credential', [...self::CREDENTIALS, 'port' => 70000]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('port');
    }

    private function assertSmsFailsWith(string $reason): void {
        $job = $this->dispatchedSmsProcessor();

        try {
            $job->handle();
            $this->fail('The gateway failure should propagate so the queue can retry it.');
        } catch (MessageNotSentException $exception) {
            $job->failed($exception);
        }

        $this->assertDatabaseHas('sms', [
            'receiver' => self::RECEIVER,
            'status' => Sms::STATUS_FAILED,
            'error_message' => $reason,
        ], 'tenant');
    }

    private function saveCredentials(): void {
        $this->app->make(AirtelSmsCredentialService::class)->updateCredentials(self::CREDENTIALS);
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
