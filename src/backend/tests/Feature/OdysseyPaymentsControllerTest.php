<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use App\Models\Company;
use App\Models\ConnectionType;
use App\Models\Device;
use App\Models\GeographicalInformation;
use App\Models\MiniGrid;
use App\Models\PaymentHistory;
use App\Models\Person\Person;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use App\Plugins\OdysseyDataExport\Models\OdysseyFinancingProgram;
use App\Plugins\OdysseyDataExport\Services\OdysseySettingService;
use Database\Factories\Address\AddressFactory;
use Database\Factories\AgentTransactionFactory;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\CityFactory;
use Database\Factories\ClusterFactory;
use Database\Factories\ConnectionTypeFactory;
use Database\Factories\EBikeFactory;
use Database\Factories\Meter\MeterFactory;
use Database\Factories\MiniGridFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\SolarHomeSystemFactory;
use Database\Factories\TokenFactory;
use Database\Factories\TransactionFactory;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class OdysseyPaymentsControllerTest extends TestCase {
    private const string URL = '/api/odyssey/payments';

    private string $apiKey;

    private int $clusterId;

    protected function setUp(): void {
        parent::setUp();

        $this->apiKey = Str::random(40);
        Company::query()->findOrFail($this->companyId)->apiKeys()->create([
            'name' => 'Odyssey',
            'token_hash' => hash('sha256', $this->apiKey),
            'active' => true,
        ]);
        $this->clusterId = ClusterFactory::new()->create(['manager_id' => UserFactory::new()->create()->id])->id;
    }

    public function testMeterEnergyPaymentIsExportedAsEnergyCredit(): void {
        $miniGrid = $this->createMiniGrid();
        $customer = $this->createCustomer($miniGrid);
        $connectionType = ConnectionTypeFactory::new()->create(['name' => 'Residential']);
        $device = $this->createMeterDevice($customer, $connectionType);
        $token = TokenFactory::new()->create(['device_id' => $device->id, 'token_unit' => Token::UNIT_KWH, 'token_amount' => 15.2]);
        $payment = $this->createPayment(Transaction::TYPE_ENERGY, $token, $customer, 1500.5);

        $exported = $this->fetchPayments()->assertOk()->json('payments.0');

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/', $exported['timestamp']);
        $this->assertSame(1500.5, $exported['amount']);
        $this->assertSame('ENERGY_CREDIT', $exported['transactionType']);
        $this->assertSame((string) $payment->transaction_id, $exported['transactionId']);
        $this->assertSame($device->device->serial_number, $exported['serialNumber']);
        $this->assertSame($device->device->serial_number, $exported['meterId']);
        $this->assertSame(15.2, $exported['transactionKwh']);
        $this->assertSame((string) $customer->id, $exported['customerId']);
        $this->assertSame('Residential', $exported['customerCategory']);
        $this->assertSame($miniGrid->id, $exported['utilityId']);
        $this->assertEquals(6.465422, $exported['latitude']);
        $this->assertEquals(3.406448, $exported['longitude']);
        $this->assertArrayNotHasKey('financingId', $exported);
    }

    public function testSolarHomeSystemInstallmentIsExportedWithItsSerial(): void {
        $customer = $this->createCustomer($this->createMiniGrid());
        $appliance = $this->createAppliance();
        $solarHomeSystem = SolarHomeSystemFactory::new()->create(['appliance_id' => $appliance->id]);
        $rate = $this->createApplianceRate($appliance, $this->createDevice($customer, $solarHomeSystem), $customer);
        $this->createPayment('installment', $rate, $customer, 200);

        $exported = $this->fetchPayments()->assertOk()->json('payments.0');

        $this->assertSame('INSTALLMENT_PAYMENT', $exported['transactionType']);
        $this->assertSame($solarHomeSystem->serial_number, $exported['serialNumber']);
        $this->assertArrayNotHasKey('meterId', $exported);
        $this->assertArrayNotHasKey('transactionKwh', $exported);
    }

    public function testEBikePaymentIsExportedWithItsSerial(): void {
        $customer = $this->createCustomer($this->createMiniGrid());
        $appliance = $this->createAppliance();
        $eBike = EBikeFactory::new()->create(['appliance_id' => $appliance->id]);
        $rate = $this->createApplianceRate($appliance, $this->createDevice($customer, $eBike), $customer);
        $this->createPayment(Transaction::TYPE_DOWN_PAYMENT, $rate, $customer, 200);

        $exported = $this->fetchPayments()->assertOk()->json('payments.0');

        $this->assertSame($eBike->serial_number, $exported['serialNumber']);
    }

    public function testPaymentTypesMapToOdysseyTransactionTypes(): void {
        $customer = $this->createCustomer($this->createMiniGrid());
        $appliance = $this->createAppliance();
        $solarHomeSystem = SolarHomeSystemFactory::new()->create(['appliance_id' => $appliance->id]);
        $rate = $this->createApplianceRate($appliance, $this->createDevice($customer, $solarHomeSystem), $customer);
        $this->createPayment(Transaction::TYPE_DOWN_PAYMENT, $rate, $customer, 100);
        $this->createPayment(Transaction::TYPE_EAAS_RATE, $rate, $customer, 100);
        $this->createPayment(Transaction::TYPE_DEFERRED_PAYMENT, $rate, $customer, 100);
        $this->createPayment('access rate', $rate, $customer, 100);

        $transactionTypes = $this->fetchPayments()->assertOk()->json('payments.*.transactionType');

        $this->assertSame(['INSTALLMENT_PAYMENT', 'INSTALLMENT_PAYMENT', 'NON_CONTRACT_PAYMENT', 'FULL_PAYMENT'], $transactionTypes);
    }

    public function testAgentAndFinancingProgramAreExported(): void {
        resolve(OdysseySettingService::class)->updateFinancingProgram(OdysseyFinancingProgram::UefSspu);
        $customer = $this->createCustomer($this->createMiniGrid());
        $device = $this->createMeterDevice($customer, ConnectionTypeFactory::new()->create());
        $token = TokenFactory::new()->create(['device_id' => $device->id]);
        $payment = $this->createPayment(Transaction::TYPE_ENERGY, $token, $customer, 100);
        $agentTransaction = AgentTransactionFactory::new()->create(['agent_id' => 42]);
        $payment->transaction->originalTransaction()->associate($agentTransaction)->save();

        $exported = $this->fetchPayments()->assertOk()->json('payments.0');

        $this->assertSame('UEF_SSPU', $exported['financingId']);
        $this->assertSame(42, $exported['agentId']);
    }

    public function testSiteIdFiltersByMiniGridIdOrName(): void {
        $miniGrid = $this->createMiniGrid('Kisumu North');
        $otherMiniGrid = $this->createMiniGrid('Kisumu South');
        $customer = $this->createCustomer($miniGrid);
        $otherCustomer = $this->createCustomer($otherMiniGrid);
        $connectionType = ConnectionTypeFactory::new()->create();
        $payment = $this->createPayment(
            Transaction::TYPE_ENERGY,
            TokenFactory::new()->create(['device_id' => $this->createMeterDevice($customer, $connectionType)->id]),
            $customer,
            100
        );
        $this->createPayment(
            Transaction::TYPE_ENERGY,
            TokenFactory::new()->create(['device_id' => $this->createMeterDevice($otherCustomer, $connectionType)->id]),
            $otherCustomer,
            100
        );

        $byId = $this->fetchPayments(['SITE_ID' => (string) $miniGrid->id])->assertOk()->json('payments.*.transactionId');
        $byName = $this->fetchPayments(['SITE_ID' => 'Kisumu North'])->assertOk()->json('payments.*.transactionId');

        $this->assertSame([(string) $payment->transaction_id], $byId);
        $this->assertSame([(string) $payment->transaction_id], $byName);
    }

    public function testRangeLongerThanADayIsRejected(): void {
        $this->fetchPayments([
            'FROM' => now()->subHours(25)->toIso8601ZuluString(),
            'TO' => now()->toIso8601ZuluString(),
        ])->assertStatus(400);
    }

    public function testRangeEndingBeforeItStartsIsRejected(): void {
        $this->fetchPayments([
            'FROM' => now()->toIso8601ZuluString(),
            'TO' => now()->subHour()->toIso8601ZuluString(),
        ])->assertStatus(400);
    }

    public function testMissingRangeIsRejectedInOdysseyFormat(): void {
        $this->withHeader('Authorization', "Bearer {$this->apiKey}")
            ->getJson(self::URL.'?TO='.now()->toIso8601ZuluString())
            ->assertStatus(400)
            ->assertJsonPath('payments', [])
            ->assertJsonPath('errors', 'The FROM field is required.');
    }

    public function testQueryCountDoesNotGrowWithPayments(): void {
        $connectionType = ConnectionTypeFactory::new()->create();
        $miniGrid = $this->createMiniGrid();
        $createMeterPayment = function () use ($connectionType, $miniGrid): void {
            $customer = $this->createCustomer($miniGrid);
            $token = TokenFactory::new()->create(['device_id' => $this->createMeterDevice($customer, $connectionType)->id]);
            $this->createPayment(Transaction::TYPE_ENERGY, $token, $customer, 100);
        };

        $createMeterPayment();
        $singlePaymentQueries = $this->countTenantQueries(fn () => $this->fetchPayments()->assertOk());

        foreach (range(1, 4) as $ignored) {
            $createMeterPayment();
        }
        $fivePaymentQueries = $this->countTenantQueries(fn () => $this->fetchPayments()->assertJsonCount(5, 'payments'));

        $this->assertSame($singlePaymentQueries, $fivePaymentQueries);
    }

    /**
     * @param array<string, string> $parameters
     */
    private function fetchPayments(array $parameters = []): TestResponse {
        $query = http_build_query($parameters + [
            'FROM' => now()->subHours(2)->toIso8601ZuluString(),
            'TO' => now()->addHour()->toIso8601ZuluString(),
        ]);

        return $this->withHeader('Authorization', "Bearer {$this->apiKey}")->getJson(self::URL.'?'.$query);
    }

    private function countTenantQueries(callable $callback): int {
        DB::connection('tenant')->flushQueryLog();
        DB::connection('tenant')->enableQueryLog();
        $callback();
        DB::connection('tenant')->disableQueryLog();

        return count(DB::connection('tenant')->getQueryLog());
    }

    private function createMiniGrid(?string $name = null): MiniGrid {
        return MiniGridFactory::new()->create(array_filter(['cluster_id' => $this->clusterId, 'name' => $name]));
    }

    private function createCustomer(MiniGrid $miniGrid): Person {
        $city = CityFactory::new()->create(['mini_grid_id' => $miniGrid->id]);
        $customer = PersonFactory::new()->create();
        $address = AddressFactory::new()->make(['city_id' => $city->id]);
        $address->owner()->associate($customer);
        $address->save();

        return $customer;
    }

    private function createMeterDevice(Person $customer, ConnectionType $connectionType): Device {
        $device = $this->createDevice($customer, MeterFactory::new()->create(['connection_type_id' => $connectionType->id]));
        $device->geo()->create(['geo_json' => GeographicalInformation::makePoint(6.465422, 3.406448)]);

        return $device;
    }

    private function createDevice(Person $customer, Model $underlying): Device {
        $device = new Device(['person_id' => $customer->id, 'device_serial' => $underlying->getAttribute('serial_number')]);
        $device->device()->associate($underlying);
        $device->save();

        return $device;
    }

    private function createAppliance(): Appliance {
        return Appliance::query()->create([
            'name' => 'Test Appliance',
            'price' => 1000,
            'appliance_type_id' => ApplianceTypeFactory::new()->create()->id,
        ]);
    }

    private function createApplianceRate(Appliance $appliance, Device $device, Person $customer): ApplianceRate {
        /** @var AppliancePerson $appliancePerson */
        $appliancePerson = AppliancePersonFactory::new()->create([
            'appliance_id' => $appliance->id,
            'person_id' => $customer->id,
            'device_serial' => $device->device_serial,
        ]);

        return ApplianceRate::query()->create([
            'appliance_person_id' => $appliancePerson->id,
            'rate_cost' => 100,
            'remaining' => 100,
            'remind' => 0,
            'due_date' => now()->addMonth(),
        ]);
    }

    private function createPayment(string $paymentType, Model $paidFor, Person $payer, float $amount): PaymentHistory {
        $transaction = TransactionFactory::new()->create(['original_transaction_id' => 0, 'original_transaction_type' => 'cash_transaction']);

        $payment = new PaymentHistory([
            'amount' => $amount,
            'payment_service' => 'test',
            'payment_type' => $paymentType,
            'sender' => '+254700000000',
            'transaction_id' => $transaction->id,
        ]);
        $payment->paidFor()->associate($paidFor);
        $payment->payer()->associate($payer);
        $payment->created_at = now()->subHour();
        $payment->save();

        return $payment;
    }
}
