<?php

declare(strict_types=1);

namespace App\Plugins\OpenPaygo\Tests\Unit;

use App\Enums\DeviceType;
use App\Events\TransactionSuccessfulEvent;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Models\Device;
use App\Models\Token;
use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\Transaction;
use App\Plugins\OpenPaygo\Exceptions\OpenPaygoIssuanceException;
use App\Plugins\OpenPaygo\Http\Clients\OpenPaygoGeneratorClient;
use App\Plugins\OpenPaygo\Models\OpenPaygoDeviceConfiguration;
use App\Plugins\OpenPaygo\Models\OpenPaygoIssuanceReservation;
use App\Plugins\OpenPaygo\Services\OpenPaygoDeviceConfigurationService;
use App\Plugins\OpenPaygo\Services\OpenPaygoIssuanceService;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class OpenPaygoIssuanceServiceTest extends TestCase {
    use MockeryPHPUnitIntegration;
    use RefreshMultipleDatabases;

    protected function setUp(): void {
        parent::setUp();
        Event::fake([TransactionSuccessfulEvent::class]);
        config()->set('services.openpaygo_generator.url', 'https://generator.example');
        config()->set('services.openpaygo_generator.api_key', 'test-key');
    }

    public function testASecondTransactionCannotIssueWhileTheDeviceHasAnUncertainReservation(): void {
        $device = $this->createDevice();
        $this->saveConfiguration($device);
        $firstTransaction = $this->createTransaction($device);
        $secondTransaction = $this->createTransaction($device);
        $generator = \Mockery::mock(OpenPaygoGeneratorClient::class)->makePartial();
        $generator->shouldReceive('generateToken')->once()->with([
            'secretKeyHex' => '0123456789abcdef0123456789abcdef',
            'startingCode' => 123,
            'counter' => 6,
            'tokenType' => 'DISABLE_PAYG',
            'restrictedDigitSet' => false,
        ])->andThrow(new \RuntimeException('timeout'));
        $service = new OpenPaygoIssuanceService($generator);

        try {
            $service->issue($firstTransaction, $device, 'unlock');
            $this->fail('Expected the timed out request to remain uncertain.');
        } catch (OpenPaygoIssuanceException $exception) {
            $this->assertStringContainsString('outcome is uncertain', $exception->getMessage());
        }

        try {
            $service->issue($secondTransaction, $device, 'unlock');
            $this->fail('Expected the active device reservation to block another transaction.');
        } catch (OpenPaygoIssuanceException $exception) {
            $this->assertStringContainsString('unresolved', $exception->getMessage());
        }

        $this->assertSame(2, OpenPaygoIssuanceReservation::query()->where('device_id', $device->id)->count());
        $waiting = OpenPaygoIssuanceReservation::query()->where('transaction_id', $secondTransaction->id)->firstOrFail();
        $this->assertSame(OpenPaygoIssuanceReservation::STATE_WAITING, $waiting->state);
        $this->assertNull($waiting->counter);
    }

    public function testRepeatedCompletedTransactionReturnsStoredTokenWithoutAnotherGeneratorCall(): void {
        $device = $this->createDevice();
        $this->saveConfiguration($device);
        $transaction = $this->createTransaction($device);
        $generator = \Mockery::mock(OpenPaygoGeneratorClient::class)->makePartial();
        $generator->shouldReceive('generateToken')->once()->andReturn([
            'token' => '123456789',
            'nextCounter' => 7,
        ]);
        $service = new OpenPaygoIssuanceService($generator);

        $firstToken = $service->issue($transaction, $device, 'unlock');
        $secondToken = $service->issue($transaction, $device, 'unlock');

        $this->assertSame($firstToken->id, $secondToken->id);
        $this->assertSame(7, new OpenPaygoDeviceConfigurationService()->getForDevice($device)['nextCounter']);
        $this->assertSame(
            OpenPaygoIssuanceReservation::STATE_COMPLETED,
            OpenPaygoIssuanceReservation::query()->where('transaction_id', $transaction->id)->value('state'),
        );
    }

    public function testCreditUsesVerifiedDividerAndPersistsMpmCreditDays(): void {
        $device = $this->createDevice();
        $this->saveConfiguration($device, 6, 4);
        $transaction = $this->createTransaction($device);
        $generator = \Mockery::mock(OpenPaygoGeneratorClient::class)->makePartial();
        $generator->shouldReceive('generateToken')->once()->with([
            'secretKeyHex' => '0123456789abcdef0123456789abcdef',
            'startingCode' => 123,
            'counter' => 6,
            'tokenType' => 'ADD_TIME',
            'restrictedDigitSet' => false,
            'value' => 60,
        ])->andReturn([
            'token' => 'credit-token',
            'nextCounter' => 7,
        ]);

        $token = new OpenPaygoIssuanceService($generator)->issue($transaction, $device, 'credit', 15);

        $this->assertSame(Token::TYPE_TIME, $token->token_type);
        $this->assertSame(Token::UNIT_DAYS, $token->token_unit);
        $this->assertSame(15, (int) $token->token_amount);
        $this->assertSame(
            60,
            OpenPaygoIssuanceReservation::query()->where('transaction_id', $transaction->id)->value('generator_value'),
        );
        $this->assertSame(
            15,
            OpenPaygoIssuanceReservation::query()->where('transaction_id', $transaction->id)->value('credit_days'),
        );
    }

    public function testCreditConversionAcceptsMinimumAndMaximumGeneratorValues(): void {
        foreach ([[1, 1], [995, 995]] as [$days, $expectedValue]) {
            $device = $this->createDevice();
            $this->saveConfiguration($device, 6, 1);
            $transaction = $this->createTransaction($device);
            $generator = \Mockery::mock(OpenPaygoGeneratorClient::class)->makePartial();
            $generator->shouldReceive('generateToken')->once()->withArgs(
                fn (array $payload): bool => $payload['tokenType'] === 'ADD_TIME'
                    && $payload['value'] === $expectedValue
            )->andReturn([
                'token' => 'boundary-token-'.$days,
                'nextCounter' => 7,
            ]);

            new OpenPaygoIssuanceService($generator)->issue($transaction, $device, 'credit', (float) $days);
        }
    }

    public function testSeparatePaymentsAdvanceTheDeviceCounterAndKeepTheirCreditAmounts(): void {
        $device = $this->createDevice();
        $this->saveConfiguration($device, 6, 4);
        $firstTransaction = $this->createTransaction($device);
        $secondTransaction = $this->createTransaction($device);
        $generator = \Mockery::mock(OpenPaygoGeneratorClient::class)->makePartial();
        $generator->shouldReceive('generateToken')->once()->withArgs(
            fn (array $payload): bool => $payload['counter'] === 6
                && $payload['tokenType'] === 'ADD_TIME'
                && $payload['value'] === 60
        )->andReturn(['token' => 'first-credit', 'nextCounter' => 7]);
        $generator->shouldReceive('generateToken')->once()->withArgs(
            fn (array $payload): bool => $payload['counter'] === 7
                && $payload['tokenType'] === 'ADD_TIME'
                && $payload['value'] === 120
        )->andReturn(['token' => 'second-credit', 'nextCounter' => 8]);
        $service = new OpenPaygoIssuanceService($generator);

        $firstToken = $service->issue($firstTransaction, $device, 'credit', 15);
        $secondToken = $service->issue($secondTransaction, $device, 'credit', 30);

        $this->assertSame('first-credit', $firstToken->token);
        $this->assertSame('second-credit', $secondToken->token);
        $this->assertSame(8, new OpenPaygoDeviceConfigurationService()->getForDevice($device)['nextCounter']);
        $this->assertSame(15, (int) $firstToken->token_amount);
        $this->assertSame(30, (int) $secondToken->token_amount);
    }

    public function testMissingLegacyDividerBlocksCreditBeforeGeneratorCall(): void {
        $device = $this->createDevice();
        $this->saveConfiguration($device);
        $transaction = $this->createTransaction($device);
        $generator = \Mockery::mock(OpenPaygoGeneratorClient::class)->makePartial();
        $generator->shouldNotReceive('generateToken');

        $this->expectExceptionMessage('time divider is missing or invalid');
        new OpenPaygoIssuanceService($generator)->issue($transaction, $device, 'credit', 1);
    }

    public function testMissingDeviceConfigurationBlocksCredit(): void {
        $device = $this->createDevice();
        $transaction = $this->createTransaction($device);
        $generator = \Mockery::mock(OpenPaygoGeneratorClient::class)->makePartial();
        $generator->shouldNotReceive('generateToken');

        $this->expectException(OpenPaygoIssuanceException::class);
        $this->expectExceptionMessage('configuration is missing');
        new OpenPaygoIssuanceService($generator)->issue($transaction, $device, 'credit', 1);
    }

    public function testInvalidAndOutOfRangeCreditIsBlockedBeforeGeneratorCall(): void {
        foreach ([[0.0, 1], [-1.0, 1], [1.5, 1], [996.0, 1], [249.0, 4]] as [$creditDays, $timeDivider]) {
            $device = $this->createDevice();
            $this->saveConfiguration($device, 6, $timeDivider);
            $transaction = $this->createTransaction($device);
            $generator = \Mockery::mock(OpenPaygoGeneratorClient::class)->makePartial();
            $generator->shouldNotReceive('generateToken');

            try {
                new OpenPaygoIssuanceService($generator)->issue($transaction, $device, 'credit', $creditDays);
                $this->fail('Expected invalid credit conversion to fail.');
            } catch (ApiCallDoesNotSupportedException|OpenPaygoIssuanceException) {
                $this->assertSame(
                    1,
                    OpenPaygoIssuanceReservation::query()->where('transaction_id', $transaction->id)->count(),
                );
            }
        }
    }

    public function testGeneratorReturnedCounterMayReachDocumentedBoundary(): void {
        $device = $this->createDevice();
        $this->saveConfiguration($device, 100000);
        $transaction = $this->createTransaction($device);
        $generator = \Mockery::mock(OpenPaygoGeneratorClient::class)->makePartial();
        $generator->shouldReceive('generateToken')->once()->andReturn([
            'token' => 'boundary-token',
            'nextCounter' => 100002,
        ]);
        $service = new OpenPaygoIssuanceService($generator);

        $token = $service->issue($transaction, $device, 'unlock');

        $this->assertSame('boundary-token', $token->token);
        $this->assertSame(100002, new OpenPaygoDeviceConfigurationService()->getForDevice($device)['nextCounter']);
    }

    public function testCounterBeyondGeneratorInputLimitBlocksBeforeExternalCall(): void {
        $device = $this->createDevice();
        $configuration = $this->saveConfiguration($device, 100000);
        $configuration->next_counter = 100001;
        $configuration->save();
        $transaction = $this->createTransaction($device);
        $generator = \Mockery::mock(OpenPaygoGeneratorClient::class)->makePartial();
        $generator->shouldNotReceive('generateToken');
        $service = new OpenPaygoIssuanceService($generator);

        $this->expectException(OpenPaygoIssuanceException::class);
        $this->expectExceptionMessage('counter is exhausted');
        $service->issue($transaction, $device, 'unlock');
    }

    public function testTransactionCannotIssueForAnotherDevice(): void {
        $device = $this->createDevice();
        $otherDevice = $this->createDevice();
        $this->saveConfiguration($device);
        $transaction = $this->createTransaction($device);
        $generator = \Mockery::mock(OpenPaygoGeneratorClient::class)->makePartial();
        $generator->shouldNotReceive('generateToken');

        $this->expectException(OpenPaygoIssuanceException::class);
        $this->expectExceptionMessage('does not belong to this device');
        new OpenPaygoIssuanceService($generator)->issue($transaction, $otherDevice, 'unlock');
    }

    public function testDuplicateUncertainRequestDoesNotCallGeneratorAgain(): void {
        $device = $this->createDevice();
        $this->saveConfiguration($device);
        $transaction = $this->createTransaction($device);
        $generator = \Mockery::mock(OpenPaygoGeneratorClient::class)->makePartial();
        $generator->shouldReceive('generateToken')->once()->with([
            'secretKeyHex' => '0123456789abcdef0123456789abcdef',
            'startingCode' => 123,
            'counter' => 6,
            'tokenType' => 'SET_TIME',
            'restrictedDigitSet' => false,
            'value' => 0,
        ])->andThrow(new \RuntimeException('connection lost'));
        $service = new OpenPaygoIssuanceService($generator);

        try {
            $service->issue($transaction, $device, 'reset');
            $this->fail('Expected the generator timeout to leave a reservation.');
        } catch (OpenPaygoIssuanceException) {
            $this->assertSame(
                OpenPaygoIssuanceReservation::STATE_UNCERTAIN,
                OpenPaygoIssuanceReservation::query()->where('transaction_id', $transaction->id)->value('state'),
            );
            $this->assertSame(
                'SET_TIME',
                OpenPaygoIssuanceReservation::query()->where('transaction_id', $transaction->id)->value('generator_operation'),
            );
            $this->assertSame(
                0,
                OpenPaygoIssuanceReservation::query()->where('transaction_id', $transaction->id)->value('generator_value'),
            );
        }

        try {
            $service->issue($transaction, $device, 'reset');
            $this->fail('Expected an uncertain request to remain blocked.');
        } catch (OpenPaygoIssuanceException $exception) {
            $this->assertStringContainsString('requires recovery', $exception->getMessage());
        }
    }

    public function testStoredGeneratorResultRecoversTokenPersistenceFailureWithoutRegeneration(): void {
        $device = $this->createDevice();
        $this->saveConfiguration($device, 14);
        $transaction = $this->createTransaction($device);
        $transaction->token()->create([
            'device_id' => $device->id,
            'token' => 'different-token',
            'token_type' => Token::TYPE_UNLOCK,
            'token_unit' => null,
            'token_amount' => null,
        ]);
        $generator = \Mockery::mock(OpenPaygoGeneratorClient::class)->makePartial();
        $generator->shouldReceive('generateToken')->once()->andReturn([
            'token' => 'stored-result',
            'nextCounter' => 19,
        ]);
        $service = new OpenPaygoIssuanceService($generator);

        try {
            $service->issue($transaction, $device, 'unlock');
            $this->fail('Expected the conflicting token row to fail persistence.');
        } catch (OpenPaygoIssuanceException $exception) {
            $this->assertStringContainsString('does not match', $exception->getMessage());
        }

        $this->assertSame(14, new OpenPaygoDeviceConfigurationService()->getForDevice($device)['nextCounter']);
        $this->assertSame(
            OpenPaygoIssuanceReservation::STATE_GENERATED,
            OpenPaygoIssuanceReservation::query()->where('transaction_id', $transaction->id)->value('state'),
        );
        $this->assertSame(
            'Stored OpenPAYGO generator result requires recovery; no new token will be generated.',
            OpenPaygoIssuanceReservation::query()->where('transaction_id', $transaction->id)->value('last_error'),
        );

        $transaction->token()->delete();
        $token = $service->issue($transaction, $device, 'unlock');

        $this->assertSame('stored-result', $token->token);
        $this->assertSame(19, new OpenPaygoDeviceConfigurationService()->getForDevice($device)['nextCounter']);
        $this->assertNull(OpenPaygoIssuanceReservation::query()->where('transaction_id', $transaction->id)->value('last_error'));
    }

    public function testCounterEditedDuringGenerationCannotBeOverwrittenByCompletion(): void {
        $device = $this->createDevice();
        $configuration = $this->saveConfiguration($device, 6);
        $transaction = $this->createTransaction($device);
        $generator = \Mockery::mock(OpenPaygoGeneratorClient::class)->makePartial();
        $generator->shouldReceive('generateToken')->once()->andReturnUsing(function () use ($configuration): array {
            $configuration->next_counter = 20;
            $configuration->save();

            return ['token' => 'stored-token', 'nextCounter' => 8];
        });
        $service = new OpenPaygoIssuanceService($generator);
        try {
            $service->issue($transaction, $device, 'unlock');
            $this->fail('An edited counter must not be overwritten by an in-flight issuance.');
        } catch (OpenPaygoIssuanceException $exception) {
            $this->assertStringContainsString('counter changed', $exception->getMessage());
        }
        $reservation = OpenPaygoIssuanceReservation::query()->where('transaction_id', $transaction->id)->firstOrFail();
        $this->assertSame(OpenPaygoIssuanceReservation::STATE_GENERATED, $reservation->state);
        $this->assertNotNull($reservation->last_error);
        $this->assertSame(20, $configuration->fresh()->next_counter);
        $this->assertSame(0, $service->recoverStaleReservations());
        $this->assertNull($transaction->token()->first());
        $configuration->next_counter = 6;
        $configuration->save();
        $this->assertSame(1, $service->recoverStaleReservations());
        $this->assertSame(8, $configuration->fresh()->next_counter);
    }

    public function testStaleRequestingReservationBecomesUncertainAndIsNeverRegenerated(): void {
        $device = $this->createDevice();
        $configuration = $this->saveConfiguration($device);
        $transaction = $this->createTransaction($device);
        $reservation = OpenPaygoIssuanceReservation::query()->create([
            'transaction_id' => $transaction->id,
            'device_id' => $device->id,
            'active_device_id' => $device->id,
            'operation' => 'unlock',
            'generator_operation' => 'DISABLE_PAYG',
            'counter' => $configuration->next_counter,
            'starting_code' => $configuration->starting_code,
            'secret_key_ciphertext' => $configuration->getRawOriginal('secret_key_hex'),
            'state' => OpenPaygoIssuanceReservation::STATE_REQUESTING,
        ]);
        $reservation->updated_at = now()->subMinutes(20);
        $reservation->save();
        $generator = \Mockery::mock(OpenPaygoGeneratorClient::class)->makePartial();
        $generator->shouldNotReceive('generateToken');
        $service = new OpenPaygoIssuanceService($generator);

        $this->assertSame(1, $service->recoverStaleReservations());
        $this->assertSame(
            OpenPaygoIssuanceReservation::STATE_UNCERTAIN,
            $reservation->fresh()->state,
        );

        $this->expectException(OpenPaygoIssuanceException::class);
        $service->issue($transaction, $device, 'unlock');
    }

    public function testOverlappingPaymentsWaitAndUseTheCompletedCounter(): void {
        $device = $this->createDevice();
        $this->saveConfiguration($device, 6, 4);
        $firstTransaction = $this->createTransaction($device);
        $secondTransaction = $this->createTransaction($device);
        $thirdTransaction = $this->createTransaction($device);
        $generator = \Mockery::mock(OpenPaygoGeneratorClient::class)->makePartial();
        $service = new OpenPaygoIssuanceService($generator);
        $generator->shouldReceive('generateToken')->once()->withArgs(
            fn (array $payload): bool => $payload['counter'] === 6 && $payload['value'] === 60,
        )->andReturnUsing(function () use ($service, $firstTransaction, $secondTransaction, $device): array {
            try {
                $service->issue($firstTransaction, $device, 'credit', 15);
                $this->fail('Duplicate in-flight request must not call the generator again.');
            } catch (OpenPaygoIssuanceException) {
                $this->assertSame(1, OpenPaygoIssuanceReservation::query()->where('transaction_id', $firstTransaction->id)->count());
            }
            try {
                $service->issue($secondTransaction, $device, 'credit', 30);
                $this->fail('Overlapping payment must wait for the current generator request.');
            } catch (OpenPaygoIssuanceException $exception) {
                $this->assertStringContainsString('waiting', $exception->getMessage());
            }
            $waiting = OpenPaygoIssuanceReservation::query()->where('transaction_id', $secondTransaction->id)->firstOrFail();
            $this->assertNull($waiting->counter);
            $this->assertNull($waiting->secret_key_ciphertext);

            return ['token' => 'first-credit', 'nextCounter' => 8];
        });
        $generator->shouldReceive('generateToken')->once()->withArgs(
            fn (array $payload): bool => $payload['counter'] === 8 && $payload['value'] === 120,
        )->andReturn(['token' => 'second-credit', 'nextCounter' => 10]);
        $generator->shouldReceive('generateToken')->once()->withArgs(
            fn (array $payload): bool => $payload['counter'] === 10 && $payload['value'] === 180,
        )->andReturn(['token' => 'third-credit', 'nextCounter' => 12]);

        $service->issue($firstTransaction, $device, 'credit', 15);
        try {
            $service->issue($thirdTransaction, $device, 'credit', 45);
            $this->fail('A new payment cannot overtake an earlier waiting payment.');
        } catch (OpenPaygoIssuanceException) {
        }
        $this->assertSame(2, $service->recoverStaleReservations());
        $this->assertSame('second-credit', $secondTransaction->token()->firstOrFail()->token);
        $this->assertSame(12, $device->openPaygoConfiguration()->firstOrFail()->next_counter);
        $this->assertSame(1, Token::query()->where('transaction_id', $secondTransaction->id)->count());
    }

    public function testMissingConfigurationIsDurablyBlockedAndRecoversWhenConfigured(): void {
        $device = $this->createDevice();
        $transaction = $this->createTransaction($device);
        $generator = \Mockery::mock(OpenPaygoGeneratorClient::class)->makePartial();
        $service = new OpenPaygoIssuanceService($generator);
        try {
            $service->issue($transaction, $device, 'credit', 15);
            $this->fail('Missing configuration must block before contacting the generator.');
        } catch (OpenPaygoIssuanceException) {
            $reservation = OpenPaygoIssuanceReservation::query()->where('transaction_id', $transaction->id)->firstOrFail();
            $this->assertSame(OpenPaygoIssuanceReservation::STATE_BLOCKED, $reservation->state);
            $this->assertSame('OpenPAYGO configuration is missing for this device.', $reservation->last_error);
            $this->assertNull($reservation->counter);
        }
        $this->saveConfiguration($device, 10, 4);
        $generator->shouldReceive('generateToken')->once()->withArgs(
            fn (array $payload): bool => $payload['counter'] === 10 && $payload['value'] === 60,
        )->andReturn(['token' => 'recovered-credit', 'nextCounter' => 12]);

        $this->assertSame(1, $service->recoverStaleReservations());
        $this->assertSame('recovered-credit', $transaction->token()->firstOrFail()->token);
    }

    public function testMissingGeneratorApiKeyBlocksBeforeReservingCounterAndRecoversSafely(): void {
        $device = $this->createDevice();
        $this->saveConfiguration($device);
        $transaction = $this->createTransaction($device);
        config()->set('services.openpaygo_generator.api_key', '');
        $generator = \Mockery::mock(OpenPaygoGeneratorClient::class)->makePartial();
        $service = new OpenPaygoIssuanceService($generator);
        try {
            $service->issue($transaction, $device, 'unlock');
            $this->fail('Missing API key must block without an external call.');
        } catch (OpenPaygoIssuanceException) {
            $reservation = OpenPaygoIssuanceReservation::query()->where('transaction_id', $transaction->id)->firstOrFail();
            $this->assertSame(OpenPaygoIssuanceReservation::STATE_BLOCKED, $reservation->state);
            $this->assertSame('OpenPAYGO generator API key is not configured.', $reservation->last_error);
            $this->assertNull($reservation->counter);
        }
        config()->set('services.openpaygo_generator.api_key', 'test-key');
        $generator->shouldReceive('generateToken')->once()->andReturn(['token' => 'verified-unlock', 'nextCounter' => 8]);
        $this->assertSame(1, $service->recoverStaleReservations());
    }

    public function testInvalidCreditDaysStayBlockedUntilPaymentIsRetriedWithCorrectDays(): void {
        $device = $this->createDevice();
        $this->saveConfiguration($device, 6, 4);
        $transaction = $this->createTransaction($device);
        $generator = \Mockery::mock(OpenPaygoGeneratorClient::class)->makePartial();
        $service = new OpenPaygoIssuanceService($generator);
        try {
            $service->issue($transaction, $device, 'credit');
            $this->fail('A payment without calculated credit days must be durably blocked.');
        } catch (OpenPaygoIssuanceException) {
            $reservation = OpenPaygoIssuanceReservation::query()->where('transaction_id', $transaction->id)->firstOrFail();
            $this->assertNull($reservation->credit_days);
            $this->assertNull($reservation->counter);
            $this->assertSame(OpenPaygoIssuanceReservation::STATE_BLOCKED, $reservation->state);
        }
        $this->assertSame(0, $service->recoverStaleReservations());
        $generator->shouldReceive('generateToken')->once()->withArgs(
            fn (array $payload): bool => $payload['counter'] === 6 && $payload['value'] === 60,
        )->andReturn(['token' => 'corrected-payment', 'nextCounter' => 8]);
        $firstToken = $service->issue($transaction, $device, 'credit', 15);
        $this->assertSame($firstToken->id, $service->issue($transaction, $device, 'credit', 15)->id);
        $this->assertSame(1, OpenPaygoIssuanceReservation::query()->where('transaction_id', $transaction->id)->count());
    }

    public function testReconciliationUnblocksWaitingPaymentWithoutRegeneratingUncertainToken(): void {
        $device = $this->createDevice();
        $this->saveConfiguration($device, 6, 4);
        $firstTransaction = $this->createTransaction($device);
        $secondTransaction = $this->createTransaction($device);
        $generator = \Mockery::mock(OpenPaygoGeneratorClient::class)->makePartial();
        $generator->shouldReceive('generateToken')->once()->withArgs(
            fn (array $payload): bool => $payload['counter'] === 6,
        )->andThrow(new \RuntimeException('timeout'));
        $generator->shouldReceive('generateToken')->once()->withArgs(
            fn (array $payload): bool => $payload['counter'] === 8,
        )->andReturn(['token' => 'second-credit', 'nextCounter' => 10]);
        $service = new OpenPaygoIssuanceService($generator);
        foreach ([$firstTransaction, $secondTransaction] as $transaction) {
            try {
                $service->issue($transaction, $device, 'credit', 15);
                $this->fail('Unresolved issuance must remain pending.');
            } catch (OpenPaygoIssuanceException) {
            }
        }
        $reservation = OpenPaygoIssuanceReservation::query()->where('transaction_id', $firstTransaction->id)->firstOrFail();
        foreach ([['invalid', 8], ['123456789', 6]] as [$token, $counter]) {
            try {
                $service->reconcileUncertainReservation($reservation->id, $token, $counter);
                $this->fail('Unverified reconciliation data must be rejected.');
            } catch (OpenPaygoIssuanceException) {
                $this->assertSame(OpenPaygoIssuanceReservation::STATE_UNCERTAIN, $reservation->fresh()->state);
            }
        }
        $configuration = $device->openPaygoConfiguration()->firstOrFail();
        $configuration->next_counter = 7;
        $configuration->save();
        try {
            $service->reconcileUncertainReservation($reservation->id, '123456789', 8);
            $this->fail('Counter edits must prevent reconciliation against an obsolete snapshot.');
        } catch (OpenPaygoIssuanceException) {
            $this->assertSame(OpenPaygoIssuanceReservation::STATE_UNCERTAIN, $reservation->fresh()->state);
        }
        $configuration->next_counter = 6;
        $configuration->save();
        $this->assertSame('123456789', $service->reconcileUncertainReservation($reservation->id, '123456789', 8)->token);
        $this->assertSame(1, $service->recoverStaleReservations());
        $this->assertSame('second-credit', $secondTransaction->token()->firstOrFail()->token);
        $this->expectException(OpenPaygoIssuanceException::class);
        $service->reconcileUncertainReservation($reservation->id, '987654321', 12);
    }

    public function testRecoveryContinuesPastConflictingStoredResultAndEmitsCompletionOnce(): void {
        $blockedDevice = $this->createDevice();
        $this->saveConfiguration($blockedDevice);
        $blockedTransaction = $this->createTransaction($blockedDevice);
        $blockedTransaction->token()->create(['device_id' => $blockedDevice->id, 'token' => 'conflict', 'token_type' => Token::TYPE_UNLOCK]);
        $healthyDevice = $this->createDevice();
        $this->saveConfiguration($healthyDevice);
        $cashTransaction = CashTransaction::query()->create(['user_id' => UserFactory::new()->create()->id, 'status' => 1]);
        $healthyTransaction = $this->createTransaction($healthyDevice);
        $healthyTransaction->type = Transaction::TYPE_DEFERRED_PAYMENT;
        $healthyTransaction->originalTransaction()->associate($cashTransaction);
        $healthyTransaction->save();
        $generator = \Mockery::mock(OpenPaygoGeneratorClient::class)->makePartial();
        $generator->shouldReceive('generateToken')->once()->andReturn(['token' => 'stored-result', 'nextCounter' => 8]);
        $service = new OpenPaygoIssuanceService($generator);
        try {
            $service->issue($blockedTransaction, $blockedDevice, 'unlock');
            $this->fail('Conflicting token must preserve its generator result for recovery.');
        } catch (OpenPaygoIssuanceException) {
        }
        $generator->shouldReceive('generateToken')->once()->andReturnUsing(function () use ($healthyDevice): array {
            $healthyDevice->openPaygoConfiguration()->delete();

            return ['token' => 'healthy-result', 'nextCounter' => 8];
        });
        try {
            $service->issue($healthyTransaction, $healthyDevice, 'unlock');
            $this->fail('Unavailable configuration must retain the generated result.');
        } catch (OpenPaygoIssuanceException) {
        }
        $healthyDevice->openPaygoConfiguration()->create([
            'secret_key_hex' => Crypt::encryptString('0123456789abcdef0123456789abcdef'),
            'starting_code' => 123,
            'next_counter' => 6,
            'time_divider' => null,
        ]);
        $this->assertSame(1, $service->recoverStaleReservations());
        $service->issue($healthyTransaction, $healthyDevice, 'unlock');
        $this->assertSame(0, $service->recoverStaleReservations());
        Event::assertDispatchedTimes(TransactionSuccessfulEvent::class, 1);
    }

    private function createDevice(): Device {
        return Device::query()->create([
            'person_id' => null,
            'device_type' => DeviceType::SolarHomeSystem->value,
            'device_id' => random_int(1, 100000000),
            'device_serial' => uniqid('openpaygo-issuance-', true),
        ]);
    }

    private function saveConfiguration(Device $device, int $counter = 6, ?int $timeDivider = null): OpenPaygoDeviceConfiguration {
        return new OpenPaygoDeviceConfigurationService()->saveForDevice(
            $device,
            '0123456789abcdef0123456789abcdef',
            123,
            $counter,
            $timeDivider,
        );
    }

    private function createTransaction(Device $device): Transaction {
        return Transaction::query()->create([
            'original_transaction_id' => 1,
            'original_transaction_type' => CashTransaction::class,
            'amount' => 0,
            'type' => Transaction::TYPE_AD_HOC,
            'sender' => '',
            'message' => $device->device_serial,
        ]);
    }
}
