<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTO\TransactionDataContainer;
use App\Enums\MpmPlugin;
use App\Enums\VendingCapability;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerDeviceControl;
use App\Lib\ITokenIssuer;
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
        $response->assertJsonPath('data.token_types', ['add_credit']);
        $response->assertJsonPath('data.credit_unit', 'days');
        $response->assertJsonPath('data.token_generation_blocked_reason', null);
    }

    public function testItOffersOnlyCreditForAManufacturerThatOnlyVendsCredit(): void {
        $this->createTestData();
        $device = $this->seedShs('CreditOnlyApi');
        $this->seedEnergyServicePlan($device, pricePerDay: 100);
        $this->bindManufacturerApi('CreditOnlyApi');

        $response = $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/capabilities");

        $response->assertStatus(200);
        $response->assertJsonPath('data.token_types', ['add_credit']);
    }

    public function testItReportsUnlockPaygoAndResetCreditForAManufacturerThatDeclaresThem(): void {
        $this->createTestData();
        $device = $this->seedShs('TokenControlApi');
        $this->bindTokenControlManufacturerApi('TokenControlApi');

        $response = $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/capabilities");

        $response->assertStatus(200);
        $response->assertJsonPath('data.token_types', ['unlock_paygo', 'reset_credit']);
    }

    /**
     * The realistic SHS case: one manufacturer that vends credit and can also unlock
     * PAYGO and reset credit, so all three have to be offered for the same device at once.
     */
    public function testItOffersAddCreditAlongsideUnlockPaygoAndResetCreditOnTheSameDevice(): void {
        $this->createTestData();
        $device = $this->seedShs('AllThreeApi');
        $this->seedEnergyServicePlan($device, pricePerDay: 100);
        $this->bindTokenControlManufacturerApi('AllThreeApi');

        $response = $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/capabilities");

        $response->assertStatus(200);
        $response->assertJsonPath('data.token_types', ['add_credit', 'unlock_paygo', 'reset_credit']);

        $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'type' => 'add_credit',
            'amount' => 500,
            'unit' => 'currency',
        ])->assertStatus(201)->assertJsonPath('data.token', 'FAKE-TOKEN');
    }

    public function testItReportsNoCapabilitiesWhenTheManufacturerHasNoApi(): void {
        $this->createTestData();
        $device = $this->seedShs(null);

        $response = $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/capabilities");

        $response->assertStatus(200);
        $response->assertJsonPath('data.token_types', []);
    }

    public function testItReportsWhyADeviceWithoutACustomerCannotBeIssuedAToken(): void {
        $this->createTestData();
        $device = $this->seedShs('BlockedApi', withCustomer: false);
        $this->bindManufacturerApi('BlockedApi');

        $response = $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/capabilities");

        $response->assertStatus(200);
        $response->assertJsonPath('data.token_types', []);
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
     * ad-hoc token issued, so the relation has to reach the endpoint.
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

    public function testItRecordsNoTransactionWhenTheManufacturerCallFails(): void {
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

    public function testItGeneratesAResetCreditTokenAtNoCharge(): void {
        $this->createTestData();
        $device = $this->seedShs('ResetApi');
        $this->seedEnergyServicePlan($device, pricePerDay: 100);
        $this->bindTokenControlManufacturerApi('ResetApi');

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'type' => 'reset_credit',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.token', 'RESET-CREDIT-TOKEN');
        $response->assertJsonPath('data.token_type', Token::TYPE_RESET_CREDIT);
        $response->assertJsonPath('data.token_unit', null);
        $response->assertJsonPath('data.token_amount', null);

        $token = Token::query()->where('device_id', $device->id)->firstOrFail();
        $transaction = Transaction::query()->findOrFail($token->transaction_id);
        $this->assertSame(Transaction::TYPE_AD_HOC, $transaction->type);
        $this->assertSame(0.0, (float) $transaction->amount);
        $this->assertInstanceOf(CashTransaction::class, $transaction->originalTransaction()->first());
        $this->assertTrue(
            Log::query()->where('affected_id', $device->id)->where('affected_type', Device::RELATION_NAME)->exists()
        );
    }

    /**
     * The case the feature exists for: the unit has already been taken back, so its
     * payment plan and its owner are gone, and the credit path would reject it.
     */
    public function testItResetsTheCreditOfADeviceWithNoCustomerAndNoAppliancePlan(): void {
        $this->createTestData();
        $device = $this->seedShs('RepossessedApi', withCustomer: false);
        $this->bindTokenControlManufacturerApi('RepossessedApi');

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'type' => 'reset_credit',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.token_type', Token::TYPE_RESET_CREDIT);
        $this->assertSame(1, Token::query()->where('device_id', $device->id)->count());
    }

    public function testItGeneratesAnUnlockPaygoToken(): void {
        $this->createTestData();
        $device = $this->seedShs('UnlockApi');
        $this->bindTokenControlManufacturerApi('UnlockApi');

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'type' => 'unlock_paygo',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.token', 'UNLOCK-PAYGO-TOKEN');
        $response->assertJsonPath('data.token_type', Token::TYPE_UNLOCK_PAYGO);
    }

    public function testItOffersOnlyResetCreditForADeviceWithoutACustomer(): void {
        $this->createTestData();
        $device = $this->seedShs('UnassignedApi', withCustomer: false);
        $this->bindTokenControlManufacturerApi('UnassignedApi');

        $response = $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/capabilities");

        $response->assertJsonPath('data.token_types', ['reset_credit']);
        $response->assertJsonPath(
            'data.token_generation_blocked_reason',
            "Device {$device->device_serial} is not assigned to a customer, so credit cannot be issued for it."
        );
    }

    public function testItRejectsAnUnlockPaygoForADeviceWithoutACustomer(): void {
        $this->createTestData();
        $device = $this->seedShs('UnassignedUnlockApi', withCustomer: false);
        $this->bindTokenControlManufacturerApi('UnassignedUnlockApi');

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'type' => 'unlock_paygo',
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Token::query()->where('device_id', $device->id)->count());
        $this->assertSame(0, Transaction::query()->where('message', $device->device_serial)->count());
    }

    public function testItGeneratesAnUnlockPaygoTokenForAnUnsoldDeviceWithTheDemoManufacturer(): void {
        $this->createTestData();
        $this->activatePlugin(MpmPlugin::DemoShsManufacturer);
        $device = $this->seedShs('DemoShsManufacturerApi');

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'type' => 'unlock_paygo',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.token_type', Token::TYPE_UNLOCK_PAYGO);
        $this->assertSame(
            1,
            Log::query()->where('affected_id', $device->id)->where('affected_type', Device::RELATION_NAME)->count()
        );
    }

    public function testItClearsTheCreditOfAMeterWithTheDemoManufacturer(): void {
        $this->createTestData();
        $this->activatePlugin(MpmPlugin::DemoMeterManufacturer);
        $device = $this->seedMeter('DemoMeterManufacturerApi');

        $this->actingAs($this->user)
            ->getJson("/api/devices/{$device->id}/capabilities")
            ->assertJsonPath('data.token_types', ['add_credit', 'clear_credit']);

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'type' => 'clear_credit',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.token_type', Token::TYPE_CLEAR_CREDIT);
        $response->assertJsonPath('data.token_amount', null);
        $this->assertSame(1, Token::query()->where('device_id', $device->id)->count());
    }

    public function testItRejectsAResetCreditForAManufacturerThatOnlyVendsCredit(): void {
        $this->createTestData();
        $device = $this->seedShs('NoResetApi');
        $this->seedEnergyServicePlan($device, pricePerDay: 100);
        $this->bindManufacturerApi('NoResetApi');

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'type' => 'reset_credit',
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Token::query()->where('device_id', $device->id)->count());
        $this->assertSame(0, Transaction::query()->where('message', $device->device_serial)->count());
    }

    public function testItRecordsNoTransactionWhenTheManufacturerResetCreditFails(): void {
        $this->createTestData();
        $device = $this->seedShs('FailingResetApi');
        $this->bindFailingTokenControlManufacturerApi('FailingResetApi');

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'type' => 'reset_credit',
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Token::query()->where('device_id', $device->id)->count());
        $this->assertSame(0, Transaction::query()->where('message', $device->device_serial)->count());
        $this->assertSame(0, CashTransaction::query()->count());
    }

    public function testItClearsTheCreditOfAMeterWhoseVendingProviderDeclaresIt(): void {
        $this->createTestData();
        $device = $this->seedMeter('ClearCreditApi');
        $this->bindClearCreditMeterApi('ClearCreditApi');

        $this->actingAs($this->user)
            ->getJson("/api/devices/{$device->id}/capabilities")
            ->assertJsonPath('data.token_types', ['add_credit', 'clear_credit']);

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'type' => 'clear_credit',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.token', 'CLEAR-CREDIT-TOKEN');
        $transaction = Transaction::query()->findOrFail($response->json('data.transaction_id'));
        $this->assertSame(0.0, (float) $transaction->amount);
    }

    public function testItRejectsAResetCreditForAMeterThatOnlyClearsCredit(): void {
        $this->createTestData();
        $device = $this->seedMeter('MeterResetApi');
        $this->bindClearCreditMeterApi('MeterResetApi');

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'type' => 'reset_credit',
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Token::query()->where('device_id', $device->id)->count());
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

        $this->actingAs($this->user)
            ->postJson("/api/devices/{$device->id}/token", ['type' => 'repossess'])
            ->assertJsonValidationErrorFor('type');

        // A credit request is what a request without a type is, so the amount it is
        // priced from stays required.
        $this->actingAs($this->user)
            ->postJson("/api/devices/{$device->id}/token", [])
            ->assertJsonValidationErrorFor('amount');
    }

    public function testItAsksForNoAmountOnAResetCreditOrUnlockPaygoRequest(): void {
        $this->createTestData();
        $device = $this->seedShs('NoAmountApi');
        $this->bindTokenControlManufacturerApi('NoAmountApi');

        $this->actingAs($this->user)
            ->postJson("/api/devices/{$device->id}/token", ['type' => 'reset_credit'])
            ->assertStatus(201);

        $this->actingAs($this->user)
            ->postJson("/api/devices/{$device->id}/token", ['type' => 'unlock_paygo'])
            ->assertStatus(201);
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
        if ($apiName !== null) {
            $this->createManufacturerPlugin($apiName);
        }
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
        if ($apiName !== null) {
            $this->createManufacturerPlugin($apiName);
        }
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
     * Binds a stand-in manufacturer API under the token issuer name of the
     * manufacturer's plugin, so the endpoints can be driven without a vendor account. It vends
     * credit only, standing in for the manufacturers that cannot unlock or reset.
     */
    private function bindManufacturerApi(string $apiName, float $issuedAmount = 5.0): object {
        $api = new class($issuedAmount) implements ITokenIssuer {
            public function __construct(private float $issuedAmount) {}

            public function capabilities(): array {
                return [VendingCapability::AddCredit];
            }

            public function addCredit(TransactionDataContainer $transactionContainer): array {
                return [
                    'token' => 'FAKE-TOKEN',
                    'token_type' => Token::TYPE_TIME,
                    'token_unit' => Token::UNIT_DAYS,
                    'token_amount' => $this->issuedAmount,
                ];
            }

            public function unlockPaygo(TransactionDataContainer $transactionContainer): array {
                throw new ApiCallDoesNotSupportedException('Unlock PAYGO is not supported.');
            }

            public function resetCredit(Device $device): array {
                throw new ApiCallDoesNotSupportedException('Reset credit is not supported.');
            }

            public function clearCredit(Device $device): array {
                throw new ApiCallDoesNotSupportedException('Clear credit is not supported.');
            }
        };

        $this->app->bind($apiName, fn () => $api);

        return $api;
    }

    /**
     * Binds a stand-in manufacturer API that declares both the unlock and the reset
     * token, standing in for the SHS manufacturers whose token API can do more than
     * top a unit up.
     */
    private function bindTokenControlManufacturerApi(string $apiName): object {
        $api = new class implements ITokenIssuer, IManufacturerDeviceControl {
            public function capabilities(): array {
                return [
                    VendingCapability::AddCredit,
                    VendingCapability::UnlockPaygo,
                    VendingCapability::ResetCredit,
                ];
            }

            public function addCredit(TransactionDataContainer $transactionContainer): array {
                return [
                    'token' => 'FAKE-TOKEN',
                    'token_type' => Token::TYPE_TIME,
                    'token_unit' => Token::UNIT_DAYS,
                    'token_amount' => 5.0,
                ];
            }

            public function unlockPaygo(TransactionDataContainer $transactionContainer): array {
                return [
                    'token' => 'UNLOCK-PAYGO-TOKEN',
                    'token_type' => Token::TYPE_UNLOCK_PAYGO,
                    'token_unit' => null,
                    'token_amount' => null,
                ];
            }

            public function resetCredit(Device $device): array {
                return [
                    'token' => 'RESET-CREDIT-TOKEN',
                    'token_type' => Token::TYPE_RESET_CREDIT,
                    'token_unit' => null,
                    'token_amount' => null,
                ];
            }

            public function clearCredit(Device $device): array {
                throw new ApiCallDoesNotSupportedException('Clear credit is not supported.');
            }

            public function getDeviceInfo(Device $device): array {
                return ['mapped' => true, 'device' => null];
            }
        };

        $this->app->bind($apiName, fn () => $api);

        return $api;
    }

    /**
     * Binds a stand-in meter API that vends credit and clears it, standing in for the
     * STS vending provider.
     */
    private function bindClearCreditMeterApi(string $apiName): void {
        $this->app->bind($apiName, fn () => new class implements ITokenIssuer {
            public function capabilities(): array {
                return [VendingCapability::AddCredit, VendingCapability::ClearCredit];
            }

            public function addCredit(TransactionDataContainer $transactionContainer): array {
                return [];
            }

            public function unlockPaygo(TransactionDataContainer $transactionContainer): array {
                throw new ApiCallDoesNotSupportedException('Unlock PAYGO is not supported.');
            }

            public function resetCredit(Device $device): array {
                throw new ApiCallDoesNotSupportedException('Reset credit is not supported.');
            }

            public function clearCredit(Device $device): array {
                return [
                    'token' => 'CLEAR-CREDIT-TOKEN',
                    'token_type' => Token::TYPE_CLEAR_CREDIT,
                    'token_unit' => null,
                    'token_amount' => null,
                ];
            }
        });
    }

    /**
     * Binds a manufacturer API that declares the reset token but rejects the call the
     * way a vendor outage does, after the ad-hoc transaction has already been written.
     */
    private function bindFailingTokenControlManufacturerApi(string $apiName): void {
        $this->app->bind($apiName, fn () => new class implements ITokenIssuer {
            public function capabilities(): array {
                return [VendingCapability::ResetCredit];
            }

            public function addCredit(TransactionDataContainer $transactionContainer): array {
                throw new ApiCallDoesNotSupportedException('Add credit is not supported.');
            }

            public function unlockPaygo(TransactionDataContainer $transactionContainer): array {
                throw new ApiCallDoesNotSupportedException('Unlock PAYGO is not supported.');
            }

            public function resetCredit(Device $device): array {
                throw new ApiCallDoesNotSupportedException('The manufacturer refused the reset.');
            }

            public function clearCredit(Device $device): array {
                throw new ApiCallDoesNotSupportedException('Clear credit is not supported.');
            }
        });
    }

    /**
     * Binds a manufacturer API that rejects the charge the way a vendor outage does,
     * after the ad-hoc transaction has already been written.
     */
    private function bindFailingManufacturerApi(string $apiName): void {
        $this->app->bind($apiName, fn () => new class implements ITokenIssuer {
            public function capabilities(): array {
                return [VendingCapability::AddCredit];
            }

            public function addCredit(TransactionDataContainer $transactionContainer): array {
                throw new ApiCallDoesNotSupportedException('The manufacturer refused the charge.');
            }

            public function unlockPaygo(TransactionDataContainer $transactionContainer): array {
                throw new ApiCallDoesNotSupportedException('Unlock PAYGO is not supported.');
            }

            public function resetCredit(Device $device): array {
                throw new ApiCallDoesNotSupportedException('Reset credit is not supported.');
            }

            public function clearCredit(Device $device): array {
                throw new ApiCallDoesNotSupportedException('Clear credit is not supported.');
            }
        });
    }
}
