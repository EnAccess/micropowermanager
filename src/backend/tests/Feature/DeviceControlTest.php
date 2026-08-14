<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTO\TransactionDataContainer;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Lib\IManufacturerDeviceControl;
use App\Models\AppliancePerson;
use App\Models\Device;
use App\Models\Log;
use App\Models\Meter\Meter;
use App\Models\SolarHomeSystem;
use App\Models\Token;
use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\Transaction;
use Database\Factories\ApplianceFactory;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\DeviceFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\Meter\MeterFactory;
use Database\Factories\Meter\MeterTypeFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\SolarHomeSystemFactory;
use Database\Factories\TariffFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class DeviceControlTest extends TestCase {
    use CreateEnvironments;

    public function testItReportsTokenGenerationForAResolvableManufacturer(): void {
        $this->createTestData();
        $device = $this->seedShs('TokenApi');
        $this->seedEnergyServicePlan($device, pricePerDay: 100);
        $this->bindManufacturerApi('TokenApi');

        $response = $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/capabilities");

        $response->assertStatus(200);
        $response->assertJsonPath('data.token_generation', true);
        $response->assertJsonPath('data.credit_unit', 'days');
        $response->assertJsonPath('data.token_generation_blocked_reason', null);
    }

    public function testItReportsNoCapabilitiesWhenTheManufacturerHasNoApi(): void {
        $this->createTestData();
        $device = $this->seedShs(null);

        $response = $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/capabilities");

        $response->assertStatus(200);
        $response->assertJsonPath('data.token_generation', false);
    }

    public function testItReportsWhyADeviceWithoutACustomerCannotBeIssuedAToken(): void {
        $this->createTestData();
        $device = $this->seedShs('BlockedApi', withCustomer: false);
        $this->bindManufacturerApi('BlockedApi');

        $response = $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/capabilities");

        $response->assertStatus(200);
        $response->assertJsonPath('data.token_generation', false);
        $response->assertJsonPath(
            'data.token_generation_blocked_reason',
            "Device {$device->device_serial} is not assigned to a customer, so credit cannot be issued for it."
        );
    }

    public function testItReportsKwhAsTheCreditUnitOfAMeter(): void {
        $this->createTestData();
        $device = $this->seedMeter('MeterApi');
        $this->bindManufacturerApi('MeterApi');

        $response = $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/capabilities");

        $response->assertJsonPath('data.credit_unit', 'kWh');
    }

    public function testItGeneratesATokenForACurrencyAmount(): void {
        $this->createTestData();
        $device = $this->seedShs('CurrencyApi');
        $this->seedEnergyServicePlan($device, pricePerDay: 100);
        $this->bindManufacturerApi('CurrencyApi');

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'amount' => 500,
            'unit' => 'currency',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.token', 'FAKE-TOKEN');
        $response->assertJsonPath('data.token_unit', 'days');

        $token = Token::query()->where('device_id', $device->id)->firstOrFail();
        $transaction = Transaction::query()->findOrFail($token->transaction_id);
        $this->assertSame(Transaction::TYPE_AD_HOC, $transaction->type);
        $this->assertSame(500.0, (float) $transaction->amount);
        $this->assertInstanceOf(CashTransaction::class, $transaction->originalTransaction()->first());
        $this->assertTrue(
            Log::query()->where('affected_id', $device->id)->where('affected_type', Device::RELATION_NAME)->exists()
        );
    }

    /**
     * The transaction detail page reads the token off the transaction to show what an
     * ad-hoc grant issued, so the relation has to reach the endpoint.
     */
    public function testItExposesTheTokenOnTheAdHocTransactionDetail(): void {
        $this->createTestData();
        $device = $this->seedShs('DetailApi');
        $this->seedEnergyServicePlan($device, pricePerDay: 100);
        $this->bindManufacturerApi('DetailApi');

        $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'amount' => 500,
            'unit' => 'currency',
        ])->assertStatus(201);

        $token = Token::query()->where('device_id', $device->id)->firstOrFail();

        $response = $this->actingAs($this->user)->getJson("/api/transactions/{$token->transaction_id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.type', Transaction::TYPE_AD_HOC);
        $response->assertJsonPath('data.token.token', 'FAKE-TOKEN');
        $response->assertJsonPath('data.token.token_unit', 'days');
    }

    public function testItPricesADayRequestWithTheApplianceDailyPrice(): void {
        $this->createTestData();
        $device = $this->seedShs('DaysApi');
        $this->seedEnergyServicePlan($device, pricePerDay: 250);
        $this->bindManufacturerApi('DaysApi');

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'amount' => 4,
            'unit' => 'days',
        ]);

        $response->assertStatus(201);

        $token = Token::query()->where('device_id', $device->id)->firstOrFail();
        $this->assertSame(1000.0, (float) Transaction::query()->findOrFail($token->transaction_id)->amount);
    }

    public function testItPricesAKwhRequestWithTheMeterTariff(): void {
        $this->createTestData();
        $device = $this->seedMeter('KwhApi', tariffPrice: 120);
        $this->bindManufacturerApi('KwhApi');

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'amount' => 3,
            'unit' => 'kWh',
        ]);

        $response->assertStatus(201);

        $token = Token::query()->where('device_id', $device->id)->firstOrFail();
        $this->assertSame(360.0, (float) Transaction::query()->findOrFail($token->transaction_id)->amount);
    }

    public function testItReportsTheCreditTheManufacturerActuallyIssued(): void {
        $this->createTestData();
        $device = $this->seedShs('RoundingApi');
        $this->seedEnergyServicePlan($device, pricePerDay: 100);
        $this->bindManufacturerApi('RoundingApi', issuedAmount: 8.0);

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'amount' => 7.25,
            'unit' => 'days',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.token_amount', 8);
    }

    public function testItRejectsACreditUnitTheDeviceDoesNotIssue(): void {
        $this->createTestData();
        $device = $this->seedShs('MismatchApi');
        $this->seedEnergyServicePlan($device, pricePerDay: 100);
        $this->bindManufacturerApi('MismatchApi');

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'amount' => 3,
            'unit' => 'kWh',
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Token::query()->where('device_id', $device->id)->count());
    }

    public function testItRejectsAnEnergyRequestWhenTheMeterTariffIsGone(): void {
        $this->createTestData();
        $device = $this->seedMeter('NoTariffApi');
        $device->device->tariff()->first()->delete();
        $this->bindManufacturerApi('NoTariffApi');

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'amount' => 3,
            'unit' => 'kWh',
        ]);

        $response->assertStatus(422);
    }

    public function testItRejectsADayRequestForADeviceWithoutAnAppliancePlan(): void {
        $this->createTestData();
        $device = $this->seedShs('NoPlanApi');
        $this->bindManufacturerApi('NoPlanApi');

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'amount' => 3,
            'unit' => 'days',
        ]);

        $response->assertStatus(422);
    }

    public function testItRejectsTokenGenerationForADeviceWithoutACustomer(): void {
        $this->createTestData();
        $device = $this->seedShs('NoCustomerApi', withCustomer: false);
        $this->bindManufacturerApi('NoCustomerApi');

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'amount' => 500,
            'unit' => 'currency',
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Token::query()->where('device_id', $device->id)->count());
        $this->assertSame(0, Transaction::query()->where('message', $device->device_serial)->count());
    }

    public function testItRejectsACurrencyAmountForADeviceWithoutAnAppliancePlan(): void {
        $this->createTestData();
        $device = $this->seedShs('UnpricedApi');
        $this->bindManufacturerApi('UnpricedApi');

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'amount' => 500,
            'unit' => 'currency',
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Transaction::query()->where('message', $device->device_serial)->count());
    }

    public function testItRejectsACurrencyAmountWhenTheMeterTariffIsGone(): void {
        $this->createTestData();
        $device = $this->seedMeter('UnpricedMeterApi');
        $device->device->tariff()->first()->delete();
        $this->bindManufacturerApi('UnpricedMeterApi');

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'amount' => 500,
            'unit' => 'currency',
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Transaction::query()->where('message', $device->device_serial)->count());
    }

    public function testItBooksNoTransactionWhenTheManufacturerCallFails(): void {
        $this->createTestData();
        $device = $this->seedShs('FailingApi');
        $this->seedEnergyServicePlan($device, pricePerDay: 100);
        $this->bindFailingManufacturerApi('FailingApi');

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'amount' => 500,
            'unit' => 'currency',
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Token::query()->where('device_id', $device->id)->count());
        $this->assertSame(0, Transaction::query()->where('message', $device->device_serial)->count());
        $this->assertSame(0, CashTransaction::query()->count());
    }

    public function testItRejectsTokenGenerationWhenTheManufacturerHasNoApi(): void {
        $this->createTestData();
        $device = $this->seedShs(null);

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'amount' => 500,
            'unit' => 'currency',
        ]);

        $response->assertStatus(422);
    }

    public function testItValidatesTheTokenRequest(): void {
        $this->createTestData();
        $device = $this->seedShs('ValidationApi');
        $this->bindManufacturerApi('ValidationApi');

        $this->actingAs($this->user)
            ->postJson("/api/devices/{$device->id}/token", ['amount' => 0, 'unit' => 'currency'])
            ->assertJsonValidationErrorFor('amount');

        $this->actingAs($this->user)
            ->postJson("/api/devices/{$device->id}/token", ['amount' => 10, 'unit' => 'weeks'])
            ->assertJsonValidationErrorFor('unit');
    }

    public function testItRequiresAuthentication(): void {
        $this->createTestData();
        $device = $this->seedShs('AuthApi');

        $this->getJson("/api/devices/{$device->id}/capabilities")->assertUnauthorized();
        $this->postJson("/api/devices/{$device->id}/token", ['amount' => 1, 'unit' => 'currency'])->assertUnauthorized();
    }

    public function testItRequiresTheTransactionsPermission(): void {
        $this->createTestData();
        $this->user->syncRoles('user');
        $device = $this->seedShs('AuthApi');

        $this->actingAs($this->user)
            ->getJson("/api/devices/{$device->id}/capabilities")
            ->assertForbidden();
    }

    private function seedShs(?string $apiName, bool $withCustomer = true): Device {
        $manufacturer = ManufacturerFactory::new()->create(['type' => 'shs', 'api_name' => $apiName]);
        $appliance = ApplianceFactory::new()->create([
            'appliance_type_id' => ApplianceTypeFactory::new()->create()->id,
        ]);
        $solarHomeSystem = SolarHomeSystemFactory::new()->create([
            'manufacturer_id' => $manufacturer->id,
            'appliance_id' => $appliance->id,
        ]);

        return DeviceFactory::new()->create([
            'person_id' => $withCustomer ? PersonFactory::new()->create()->id : null,
            'device_id' => $solarHomeSystem->id,
            'device_type' => SolarHomeSystem::RELATION_NAME,
        ]);
    }

    private function seedMeter(?string $apiName, float $tariffPrice = 100): Device {
        $manufacturer = ManufacturerFactory::new()->create(['type' => 'meter', 'api_name' => $apiName]);
        $meter = MeterFactory::new()->create([
            'manufacturer_id' => $manufacturer->id,
            'meter_type_id' => MeterTypeFactory::new()->create()->id,
            'tariff_id' => TariffFactory::new()->create(['price' => $tariffPrice])->id,
            'serial_number' => str_random(12),
        ]);

        return DeviceFactory::new()->create([
            'person_id' => PersonFactory::new()->create()->id,
            'device_id' => $meter->id,
            'device_type' => Meter::RELATION_NAME,
        ]);
    }

    private function seedEnergyServicePlan(Device $device, int $pricePerDay): AppliancePerson {
        return AppliancePersonFactory::new()->create([
            'appliance_id' => ApplianceFactory::new()->create([
                'appliance_type_id' => ApplianceTypeFactory::new()->create()->id,
            ])->id,
            'person_id' => $device->person_id,
            'device_serial' => $device->device_serial,
            'payment_type' => AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE,
            'price_per_day' => $pricePerDay,
        ]);
    }

    /**
     * Binds a stand-in manufacturer API to the alias the device's manufacturer
     * resolves by, so the endpoints can be driven without a vendor account.
     */
    private function bindManufacturerApi(string $apiName, float $issuedAmount = 5.0): object {
        $api = new class($issuedAmount) implements IManufacturerAPI, IManufacturerDeviceControl {
            public function __construct(private float $issuedAmount) {}

            public function chargeDevice(TransactionDataContainer $transactionContainer): array {
                return [
                    'token' => 'FAKE-TOKEN',
                    'token_type' => Token::TYPE_TIME,
                    'token_unit' => Token::UNIT_DAYS,
                    'token_amount' => $this->issuedAmount,
                ];
            }

            public function unlockDevice(TransactionDataContainer $transactionContainer): array {
                return [];
            }

            public function clearDevice(Device $device): ?array {
                return null;
            }

            public function getDeviceInfo(Device $device): array {
                return ['mapped' => true, 'device' => null];
            }
        };

        $this->app->bind($apiName, fn () => $api);

        return $api;
    }

    /**
     * Binds a manufacturer API that rejects the charge the way a vendor outage does,
     * after the ad-hoc transaction has already been written.
     */
    private function bindFailingManufacturerApi(string $apiName): void {
        $this->app->bind($apiName, fn () => new class implements IManufacturerAPI {
            public function chargeDevice(TransactionDataContainer $transactionContainer): array {
                throw new ApiCallDoesNotSupportedException('The manufacturer refused the charge.');
            }

            public function unlockDevice(TransactionDataContainer $transactionContainer): array {
                return [];
            }

            public function clearDevice(Device $device): ?array {
                return null;
            }
        });
    }
}
