<?php

namespace Tests\Feature;

use App\Jobs\SmsProcessor;
use App\Models\MainSettings;
use App\Models\MpmPlugin;
use App\Models\Sms;
use App\Models\User;
use App\Plugins\TextbeeSmsGateway\Exceptions\MessageNotSentException;
use App\Plugins\TextbeeSmsGateway\TextbeeSmsGateway;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class SmsDeliveryStatusTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string RECEIVER = '+255712345678';
    private const string MESSAGE = 'Your token is 1234';

    private User $user;

    protected function setUp(): void {
        parent::setUp();

        // AbstractJob resolves the tenant it belongs to from the first user on record.
        $this->user = UserFactory::new()->create(['company_id' => $this->companyId]);
    }

    public function testSuccessfulSendMarksTheSmsAsSent(): void {
        $this->configureTextbeeGateway();
        $this->mock(TextbeeSmsGateway::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendSms')->once();
        });

        $this->dispatchedSmsProcessor()->handle();

        $this->assertDatabaseHas('sms', [
            'receiver' => self::RECEIVER,
            'status' => Sms::STATUS_SENT,
            'gateway_id' => MpmPlugin::TEXTBEE_SMS_GATEWAY,
            'error_message' => null,
        ], 'tenant');
    }

    public function testGatewayFailureRecordsTheReasonOnTheSms(): void {
        $this->configureTextbeeGateway();
        $reason = 'TextBee message sending failed: Monthly SMS limit reached';
        $this->mock(TextbeeSmsGateway::class, function (MockInterface $mock) use ($reason): void {
            $mock->shouldReceive('sendSms')->andThrow(new MessageNotSentException($reason));
        });

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

    public function testUnresolvableGatewayMarksTheSmsFailedWithoutRetrying(): void {
        // No main settings, so no gateway can be resolved for the receiver.
        $this->dispatchedSmsProcessor()->handle();

        $sms = Sms::query()->where('receiver', self::RECEIVER)->firstOrFail();

        $this->assertSame(Sms::STATUS_FAILED, $sms->status);
        $this->assertStringContainsString('No active SMS provider', (string) $sms->error_message);
    }

    public function testConversationListCountsFailedMessagesPerReceiver(): void {
        foreach ([Sms::STATUS_FAILED, Sms::STATUS_FAILED, Sms::STATUS_SENT] as $status) {
            Sms::query()->create([
                'receiver' => self::RECEIVER,
                'body' => self::MESSAGE,
                'direction' => Sms::DIRECTION_OUTGOING,
                'status' => $status,
            ]);
        }

        $response = $this->actingAs($this->user)->get('/api/sms');
        $response->assertOk();

        $conversation = collect($response['data'])->firstWhere('receiver', self::RECEIVER);

        $this->assertSame(3, $conversation['total']);
        $this->assertSame(2, $conversation['failed_total']);
    }

    private function configureTextbeeGateway(): void {
        MainSettings::query()->firstOrFail()->update(['sms_gateway_id' => MpmPlugin::TEXTBEE_SMS_GATEWAY]);
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
