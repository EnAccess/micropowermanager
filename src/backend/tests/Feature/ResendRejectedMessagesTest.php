<?php

namespace Tests\Feature;

use App\Console\Commands\ResendRejectedMessages;
use App\Models\Sms;
use App\Models\SmsAndroidSetting;
use App\Services\SmsGatewayResolverService;
use App\Sms\AndroidGateway;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class ResendRejectedMessagesTest extends TestCase {
    private function createFailedSms(int $attempts = 0): Sms {
        return Sms::query()->create([
            'receiver' => '255700000001',
            'body' => 'test message',
            'direction' => Sms::DIRECTION_OUTGOING,
            'status' => Sms::STATUS_FAILED,
            'attempts' => $attempts,
        ]);
    }

    /**
     * The console kernel instantiates commands before the test can bind mocks,
     * so the command is resolved fresh here to pick up the mocked services.
     */
    private function runCommand(): int {
        $command = $this->app->make(ResendRejectedMessages::class);
        $command->setLaravel($this->app);

        return $command->run(
            new ArrayInput(['amount' => '5', '--company-id' => '1']),
            new BufferedOutput(),
        );
    }

    private function mockResolverWithFailingGateway(): void {
        $this->mock(SmsGatewayResolverService::class, function (MockInterface $mock) {
            $mock->shouldReceive('isSmsGatewayConfigured')->andReturn(true);
            $mock->shouldReceive('determineGateway')->andThrow(new \RuntimeException('gateway unreachable'));
        });
    }

    private function mockResolverWithWorkingGateway(): void {
        $gateway = \Mockery::mock(AndroidGateway::class);
        $gateway->shouldReceive('sendSms');

        $this->mock(SmsGatewayResolverService::class, function (MockInterface $mock) use ($gateway) {
            $mock->shouldReceive('isSmsGatewayConfigured')->andReturn(true);
            $mock->shouldReceive('determineGateway')->andReturn([SmsGatewayResolverService::DEFAULT_GATEWAY, null]);
            $mock->shouldReceive('resolveGatewayAndArgs')->andReturn([
                'gateway' => $gateway,
                'args' => ['255700000001', 'test message', '', new SmsAndroidSetting()],
                'gatewayId' => SmsGatewayResolverService::DEFAULT_GATEWAY_ID,
            ]);
        });
    }

    public function testFailedResendIncrementsAttemptsAndLogsWarning(): void {
        $sms = $this->createFailedSms();
        $this->mockResolverWithFailingGateway();
        Log::spy();

        $this->assertEquals(0, $this->runCommand());

        $sms->refresh();
        $this->assertEquals(1, $sms->attempts);
        $this->assertEquals(Sms::STATUS_FAILED, $sms->status);
        $this->assertEquals('gateway unreachable', $sms->error_message);
        Log::shouldHaveReceived('warning')->with("Failed to resend message {$sms->id}: gateway unreachable");
        Log::shouldNotHaveReceived('error');
    }

    public function testFinalAttemptLogsErrorInsteadOfWarning(): void {
        $sms = $this->createFailedSms(attempts: ResendRejectedMessages::MAX_ATTEMPTS - 1);
        $this->mockResolverWithFailingGateway();
        Log::spy();

        $this->assertEquals(0, $this->runCommand());

        $sms->refresh();
        $this->assertEquals(ResendRejectedMessages::MAX_ATTEMPTS, $sms->attempts);
        Log::shouldHaveReceived('error')->with("Failed to resend message {$sms->id}: gateway unreachable");
        Log::shouldNotHaveReceived('warning');
    }

    public function testMessagesBeyondRetryCapAreNotResent(): void {
        $sms = $this->createFailedSms(attempts: ResendRejectedMessages::MAX_ATTEMPTS);

        $this->mock(SmsGatewayResolverService::class, function (MockInterface $mock) {
            $mock->shouldReceive('isSmsGatewayConfigured')->andReturn(true);
            $mock->shouldNotReceive('determineGateway');
        });

        $this->assertEquals(0, $this->runCommand());

        $sms->refresh();
        $this->assertEquals(ResendRejectedMessages::MAX_ATTEMPTS, $sms->attempts);
        $this->assertEquals(Sms::STATUS_FAILED, $sms->status);
    }

    public function testSuccessfulResendMarksMessageSent(): void {
        $sms = $this->createFailedSms(attempts: 1);
        $this->mockResolverWithWorkingGateway();

        $this->assertEquals(0, $this->runCommand());

        $sms->refresh();
        $this->assertEquals(Sms::STATUS_SENT, $sms->status);
        $this->assertEquals(SmsGatewayResolverService::DEFAULT_GATEWAY_ID, $sms->gateway_id);
        $this->assertNull($sms->error_message);
        $this->assertEquals(2, $sms->attempts);
    }

    /**
     * A failed delivery report leaves a message that the gateway already accepted and billed
     * back at STATUS_FAILED. It must be resent once and then stay out of the resend queue,
     * even though each successful hand-off resets its status to sent.
     */
    public function testDeliveryFailureAfterSuccessfulSendIsResentOnlyOnce(): void {
        $sms = $this->createFailedSms(attempts: 1);
        $this->mockResolverWithWorkingGateway();

        $this->assertEquals(0, $this->runCommand());

        $sms->refresh();
        $this->assertEquals(Sms::STATUS_SENT, $sms->status);
        $this->assertEquals(2, $sms->attempts);

        $sms->update(['status' => Sms::STATUS_FAILED]);

        $this->assertEquals(0, $this->runCommand());

        $sms->refresh();
        $this->assertEquals(2, $sms->attempts);
        $this->assertEquals(Sms::STATUS_FAILED, $sms->status);
    }

    public function testNoResendWhenGatewayIsNotConfigured(): void {
        $sms = $this->createFailedSms(attempts: 1);

        $this->mock(SmsGatewayResolverService::class, function (MockInterface $mock) {
            $mock->shouldReceive('isSmsGatewayConfigured')->andReturn(false);
            $mock->shouldNotReceive('determineGateway');
        });

        $this->assertEquals(0, $this->runCommand());

        $sms->refresh();
        $this->assertEquals(1, $sms->attempts);
        $this->assertEquals(Sms::STATUS_FAILED, $sms->status);
    }
}
