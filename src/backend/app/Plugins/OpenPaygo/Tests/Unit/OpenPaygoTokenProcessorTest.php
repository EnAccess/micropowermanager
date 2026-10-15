<?php

declare(strict_types=1);

namespace App\Plugins\OpenPaygo\Tests\Unit;

use App\DTO\TransactionDataContainer;
use App\Enums\DeviceType;
use App\Events\AppliancePersonCreated;
use App\Events\TransactionSuccessfulEvent;
use App\Jobs\ApplianceTransactionProcessor;
use App\Jobs\TokenProcessor;
use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\ApplianceType;
use App\Models\Device;
use App\Models\Manufacturer;
use App\Models\SolarHomeSystem;
use App\Models\Token;
use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\Transaction;
use App\Plugins\OpenPaygo\Exceptions\OpenPaygoGeneratorException;
use App\Plugins\OpenPaygo\Http\Clients\OpenPaygoGeneratorClient;
use App\Plugins\OpenPaygo\Models\OpenPaygoIssuanceReservation;
use App\Plugins\OpenPaygo\Services\OpenPaygoDeviceConfigurationService;
use Database\Factories\Person\PersonFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class OpenPaygoTokenProcessorTest extends TestCase {
    use MockeryPHPUnitIntegration;
    use RefreshMultipleDatabases;

    public function testCompletedOpenPaygoTransactionWithRecreateDoesNotCallGeneratorAgain(): void {
        $device = Device::query()->create([
            'person_id' => null,
            'device_type' => DeviceType::SolarHomeSystem->value,
            'device_id' => 1,
            'device_serial' => uniqid('openpaygo-job-', true),
        ]);
        new OpenPaygoDeviceConfigurationService()->saveForDevice(
            $device,
            '0123456789abcdef0123456789abcdef',
            123,
            4,
        );
        $transaction = Transaction::query()->create([
            'original_transaction_id' => 1,
            'original_transaction_type' => CashTransaction::class,
            'amount' => 0,
            'type' => Transaction::TYPE_AD_HOC,
            'sender' => '',
            'message' => $device->device_serial,
        ]);
        $container = new TransactionDataContainer();
        $container->transaction = $transaction;
        $container->device = $device;
        $container->manufacturer = new Manufacturer(['name' => 'OpenPAYGO', 'api_name' => 'OpenPaygoApi']);
        $container->appliancePerson = null;
        $container->applianceInstallmentsFullFilled = true;
        $container->paidRates = [];
        config()->set('services.openpaygo_generator.url', 'https://generator.example');
        config()->set('services.openpaygo_generator.api_key', 'test-key');
        Http::fake([
            'https://generator.example/generate' => Http::response([
                'token' => '123456789',
                'nextCounter' => 6,
            ]),
        ]);
        Event::fake([TransactionSuccessfulEvent::class]);

        new TokenProcessor(1, $container)->executeJob();
        new TokenProcessor(1, $container, true)->executeJob();

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->data()['tokenType'] === 'DISABLE_PAYG'
            && !array_key_exists('value', $request->data()));
        $this->assertSame(1, Token::query()->where('transaction_id', $transaction->id)->count());
        $this->assertSame(
            Token::TYPE_UNLOCK,
            Token::query()->where('transaction_id', $transaction->id)->value('token_type'),
        );
        $this->assertSame(
            OpenPaygoIssuanceReservation::STATE_COMPLETED,
            OpenPaygoIssuanceReservation::query()->where('transaction_id', $transaction->id)->value('state'),
        );
    }

    public function testPartialPaymentIssuesMpmCalculatedDaysAndDuplicateJobReusesToken(): void {
        $device = Device::query()->create([
            'person_id' => null,
            'device_type' => DeviceType::SolarHomeSystem->value,
            'device_id' => 2,
            'device_serial' => uniqid('openpaygo-credit-', true),
        ]);
        new OpenPaygoDeviceConfigurationService()->saveForDevice(
            $device,
            '0123456789abcdef0123456789abcdef',
            123,
            4,
            4,
        );
        $transaction = Transaction::query()->create([
            'original_transaction_id' => 1,
            'original_transaction_type' => CashTransaction::class,
            'amount' => 50,
            'type' => Transaction::TYPE_AD_HOC,
            'sender' => '',
            'message' => $device->device_serial,
        ]);
        $container = new TransactionDataContainer();
        $container->transaction = $transaction;
        $container->device = $device;
        $container->manufacturer = new Manufacturer(['name' => 'OpenPAYGO', 'api_name' => 'OpenPaygoApi']);
        $container->appliancePerson = null;
        $container->amount = 50;
        $container->installmentCost = 100;
        $container->dayDifferenceBetweenTwoInstallments = 30;
        $container->applianceInstallmentsFullFilled = false;
        $container->paidRates = [];
        config()->set('services.openpaygo_generator.url', 'https://generator.example');
        config()->set('services.openpaygo_generator.api_key', 'test-key');
        Http::fake([
            'https://generator.example/generate' => Http::response([
                'token' => 'time-credit-token',
                'nextCounter' => 6,
            ]),
        ]);
        Event::fake([TransactionSuccessfulEvent::class]);

        new TokenProcessor(1, $container)->executeJob();
        new TokenProcessor(1, $container, true)->executeJob();

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->data()['tokenType'] === 'ADD_TIME'
            && $request->data()['value'] === 60);
        $token = Token::query()->where('transaction_id', $transaction->id)->firstOrFail();
        $this->assertSame(Token::TYPE_TIME, $token->token_type);
        $this->assertSame(Token::UNIT_DAYS, $token->token_unit);
        $this->assertSame(15.0, (float) $token->token_amount);
        $this->assertSame(
            OpenPaygoIssuanceReservation::STATE_COMPLETED,
            OpenPaygoIssuanceReservation::query()->where('transaction_id', $transaction->id)->value('state'),
        );
    }

    public function testUncertainGeneratorOutcomeDoesNotQueueAnAutomaticRetry(): void {
        $device = Device::query()->create([
            'person_id' => null,
            'device_type' => DeviceType::SolarHomeSystem->value,
            'device_id' => 1,
            'device_serial' => uniqid('openpaygo-timeout-', true),
        ]);
        new OpenPaygoDeviceConfigurationService()->saveForDevice(
            $device,
            '0123456789abcdef0123456789abcdef',
            123,
            4,
        );
        $transaction = Transaction::query()->create([
            'original_transaction_id' => 1,
            'original_transaction_type' => CashTransaction::class,
            'amount' => 0,
            'type' => Transaction::TYPE_AD_HOC,
            'sender' => '',
            'message' => $device->device_serial,
        ]);
        $container = new TransactionDataContainer();
        $container->transaction = $transaction;
        $container->device = $device;
        $container->manufacturer = new Manufacturer(['name' => 'OpenPAYGO', 'api_name' => 'OpenPaygoApi']);
        $container->appliancePerson = null;
        $container->applianceInstallmentsFullFilled = true;
        $container->paidRates = [];
        $generator = \Mockery::mock(OpenPaygoGeneratorClient::class);
        $generator->shouldReceive('assertConfigured')->once();
        $generator->shouldReceive('generateToken')->once()->andThrow(new OpenPaygoGeneratorException('timeout'));
        $this->app->instance(OpenPaygoGeneratorClient::class, $generator);
        Queue::fake();
        Event::fake([TransactionSuccessfulEvent::class]);

        new TokenProcessor(1, $container)->executeJob();

        Queue::assertNothingPushed();
        $this->assertSame(
            OpenPaygoIssuanceReservation::STATE_UNCERTAIN,
            OpenPaygoIssuanceReservation::query()->where('transaction_id', $transaction->id)->value('state'),
        );
    }

    public function testRetryingWholePaymentDoesNotRepeatFinancialCreditOrTokenIssuance(): void {
        Event::fake([AppliancePersonCreated::class, TransactionSuccessfulEvent::class]);
        Queue::fake();
        $person = PersonFactory::new()->create();
        $manufacturer = Manufacturer::query()->create(['name' => 'OpenPAYGO', 'type' => 'shs', 'api_name' => 'OpenPaygoApi']);
        $applianceType = ApplianceType::query()->create(['name' => 'Test PAYGO', 'paygo_enabled' => true]);
        $appliance = Appliance::query()->create(['name' => 'Test SHS', 'price' => 0, 'appliance_type_id' => $applianceType->id]);
        $serial = uniqid('openpaygo-retry-', true);
        $solarHomeSystem = SolarHomeSystem::query()->create([
            'serial_number' => $serial, 'manufacturer_id' => $manufacturer->id, 'appliance_id' => $appliance->id,
        ]);
        $device = Device::query()->create([
            'person_id' => $person->id, 'device_type' => DeviceType::SolarHomeSystem->value,
            'device_id' => $solarHomeSystem->id, 'device_serial' => $serial,
        ]);
        $sale = AppliancePerson::query()->create([
            'person_id' => $person->id, 'appliance_id' => $appliance->id, 'device_serial' => $serial,
            'payment_type' => AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE, 'price_per_day' => 10,
            'minimum_payable_amount' => 10, 'total_cost' => 0, 'rate_count' => 0,
            'down_payment' => 0, 'creator_type' => 'user', 'creator_id' => 1,
        ]);
        $cashTransaction = CashTransaction::query()->create(['user_id' => 1, 'status' => BasePaymentProviderTransaction::STATUS_SUCCESS]);
        $transaction = Transaction::query()->create([
            'original_transaction_type' => CashTransaction::RELATION_NAME, 'original_transaction_id' => $cashTransaction->id,
            'amount' => 20, 'type' => Transaction::TYPE_EAAS_RATE, 'message' => $serial, 'sender' => '',
        ]);
        new OpenPaygoDeviceConfigurationService()->saveForDevice($device, '0123456789abcdef0123456789abcdef', 123, 0, 1);
        config()->set('services.openpaygo_generator.url', 'https://generator.example');
        config()->set('services.openpaygo_generator.api_key', 'test-key');
        Http::fake(['https://generator.example/generate' => Http::response(['token' => '123456789', 'nextCounter' => 2])]);

        new ApplianceTransactionProcessor($this->companyId, $transaction->id)->executeJob();
        new ApplianceTransactionProcessor($this->companyId, $transaction->id)->executeJob();
        $container = TransactionDataContainer::initialize($transaction->fresh());
        new TokenProcessor($this->companyId, $container)->executeJob();
        new TokenProcessor($this->companyId, $container, true)->executeJob();

        $this->assertSame(1, $sale->rates()->count());
        $this->assertSame(1, $transaction->paymentHistories()->count());
        $this->assertSame(20.0, (float) $transaction->paymentHistories()->sum('amount'));
        $this->assertSame(1, $transaction->token()->count());
        $this->assertSame(2.0, (float) $transaction->token()->firstOrFail()->token_amount);
        $this->assertSame(2, new OpenPaygoDeviceConfigurationService()->getForDevice($device)['nextCounter']);
        Http::assertSentCount(1);
        Event::assertDispatchedTimes(TransactionSuccessfulEvent::class, 1);
    }
}
