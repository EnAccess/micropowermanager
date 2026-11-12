<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTO\TransactionDataContainer;
use App\Enums\ManufacturerCapability;
use App\Events\PaymentSuccessEvent;
use App\Events\TransactionFailedEvent;
use App\Events\TransactionSuccessfulEvent;
use App\Jobs\TokenProcessor;
use App\Lib\IVendingProvider;
use App\Lib\VendingProvider;
use App\Lib\VendingProviderRegistry;
use App\Models\Device;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\MpmPlugin;
use App\Models\Plugins;
use App\Models\Token;
use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\Transaction;
use App\Services\ExportServices\DeviceExportService;
use Database\Factories\DeviceFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\Meter\MeterFactory;
use Database\Factories\Meter\MeterTypeFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\TariffFactory;
use Database\Factories\TransactionFactory;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Tests\CreateEnvironments;
use Tests\TestCase;

class MeterVendingProviderTest extends TestCase {
    use CreateEnvironments;

    private const string PROVIDER = 'test_provider';
    private const string SUPPORTED_API_NAME = 'SupportedOemApi';
    private const string UNSUPPORTED_API_NAME = 'UnsupportedOemApi';
    private const string UNBOUND_API_NAME = 'UnboundOemApi';

    private IVendingProvider $provider;

    protected function setUp(): void {
        parent::setUp();
        $this->createTestData();
        $this->bindOemApi(self::SUPPORTED_API_NAME);
        $this->bindOemApi(self::UNSUPPORTED_API_NAME);
        $this->provider = $this->registerProvider(self::PROVIDER, MpmPlugin::CALIN_METER, [self::SUPPORTED_API_NAME, self::UNBOUND_API_NAME]);
        Plugins::query()->create(['mpm_plugin_id' => MpmPlugin::CALIN_METER, 'status' => Plugins::ACTIVE]);
    }

    public function testADisabledProviderStopsVendingAMeterItIsConfiguredFor(): void {
        Event::fake([PaymentSuccessEvent::class, TransactionSuccessfulEvent::class, TransactionFailedEvent::class]);
        $device = $this->seedMeter(self::SUPPORTED_API_NAME, self::PROVIDER);
        $this->provider->configured = true;
        $this->disableProviderPlugin();

        $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/capabilities")
            ->assertStatus(200)
            ->assertJsonPath('data.token_generation', false);

        $transaction = $this->payment($device);
        new TokenProcessor($this->companyId, TransactionDataContainer::initialize($transaction))->handle();

        Event::assertDispatched(
            TransactionFailedEvent::class,
            fn (TransactionFailedEvent $event): bool => str_contains($event->message, 'its vending provider '.self::PROVIDER.' is unavailable'),
        );
        $this->assertFalse(Token::query()->where('transaction_id', $transaction->id)->exists());
    }

    public function testADisabledProviderHandsAnUnconfiguredMeterBackToItsManufacturer(): void {
        $device = $this->seedMeter(self::SUPPORTED_API_NAME, self::PROVIDER);
        $this->disableProviderPlugin();

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'type' => 'credit',
            'amount' => 1000,
            'unit' => 'currency',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.token', 'OEM-TOKEN');
    }

    public function testADisabledProviderFailsAMeterWhoseManufacturerHasNoApi(): void {
        $device = $this->seedMeter(self::UNBOUND_API_NAME, self::PROVIDER);
        $this->disableProviderPlugin();

        $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/capabilities")
            ->assertStatus(200)
            ->assertJsonPath('data.token_generation', false);
    }

    public function testAProviderWhosePluginIsDisabledCannotBeSet(): void {
        $device = $this->seedMeter(self::SUPPORTED_API_NAME, null);
        $this->disableProviderPlugin();

        $this->updateMeter($device, ['vending_provider' => self::PROVIDER])
            ->assertStatus(422)
            ->assertJsonPath('errors.vending_provider.0', 'The vending provider '.self::PROVIDER.' is disabled.');
    }

    public function testCapabilitiesComeFromTheVendingProviderWhenSet(): void {
        $withProvider = $this->seedMeter(self::SUPPORTED_API_NAME, self::PROVIDER);
        $withoutProvider = $this->seedMeter(self::SUPPORTED_API_NAME, null);

        $this->actingAs($this->user)->getJson("/api/devices/{$withProvider->id}/capabilities")
            ->assertStatus(200)
            ->assertJsonPath('data.reset_token', true);
        $this->actingAs($this->user)->getJson("/api/devices/{$withoutProvider->id}/capabilities")
            ->assertStatus(200)
            ->assertJsonPath('data.reset_token', false);
    }

    public function testAdHocCreditVendsThroughTheVendingProvider(): void {
        $device = $this->seedMeter(self::SUPPORTED_API_NAME, self::PROVIDER);

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'type' => 'credit',
            'amount' => 1000,
            'unit' => 'currency',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.token', 'PROVIDER-TOKEN');
    }

    public function testAPaymentVendsThroughTheVendingProvider(): void {
        Event::fake([PaymentSuccessEvent::class, TransactionSuccessfulEvent::class, TransactionFailedEvent::class]);
        $device = $this->seedMeter(self::SUPPORTED_API_NAME, self::PROVIDER);
        $transaction = $this->payment($device);

        new TokenProcessor($this->companyId, TransactionDataContainer::initialize($transaction))->handle();

        Event::assertNotDispatched(TransactionFailedEvent::class);
        $this->assertSame('PROVIDER-TOKEN', Token::query()->where('transaction_id', $transaction->id)->firstOrFail()->token);
    }

    public function testAMeterCanBeSwitchedToASupportingProviderAndBack(): void {
        $device = $this->seedMeter(self::SUPPORTED_API_NAME, null);

        $this->updateMeter($device, ['vending_provider' => self::PROVIDER])->assertStatus(200);
        $this->assertSame(self::PROVIDER, $device->device->refresh()->vending_provider);

        $this->updateMeter($device, ['vending_provider' => null])->assertStatus(200);
        $this->assertNull($device->device->refresh()->vending_provider);
    }

    public function testAProviderThatDoesNotSupportTheManufacturerIsRejected(): void {
        $device = $this->seedMeter(self::UNSUPPORTED_API_NAME, null);

        $this->updateMeter($device, ['vending_provider' => self::PROVIDER])
            ->assertStatus(422)
            ->assertJsonValidationErrors('vending_provider');
        $this->updateMeter($device, ['vending_provider' => 'unknown_provider'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('vending_provider');
        $this->assertNull($device->device->refresh()->vending_provider);
    }

    public function testAManufacturerTheProviderDoesNotSupportIsRejected(): void {
        $device = $this->seedMeter(self::SUPPORTED_API_NAME, self::PROVIDER);
        $unsupported = ManufacturerFactory::new()->create(['type' => 'meter', 'api_name' => self::UNSUPPORTED_API_NAME]);

        $this->updateMeter($device, ['manufacturer_id' => $unsupported->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('vending_provider');
        $this->assertNotSame($unsupported->id, $device->device->refresh()->manufacturer_id);
    }

    public function testItListsOnlyProvidersThatSupportTheManufacturerAndArePluggedIn(): void {
        $this->registerProvider('inactive_provider', MpmPlugin::KELIN_METER, [self::SUPPORTED_API_NAME]);
        $this->registerProvider('other_oem_provider', MpmPlugin::CALIN_METER, [self::UNSUPPORTED_API_NAME]);
        $device = $this->seedMeter(self::SUPPORTED_API_NAME, null);

        $response = $this->actingAs($this->user)->getJson("/api/meters/{$device->device_id}/vending-providers");

        $response->assertStatus(200);
        $response->assertExactJson(['data' => [['key' => self::PROVIDER, 'name' => 'Test provider']]]);
    }

    public function testImportSetsTheProviderOnlyForASupportedManufacturer(): void {
        $supported = ManufacturerFactory::new()->create(['type' => 'meter', 'api_name' => self::SUPPORTED_API_NAME]);
        $unsupported = ManufacturerFactory::new()->create(['type' => 'meter', 'api_name' => self::UNSUPPORTED_API_NAME]);

        $response = $this->actingAs($this->user)->postJson('/api/import/devices', ['data' => [
            ['device_info' => ['serial_number' => 'PROVIDER-OK', 'manufacturer' => $supported->name, 'vending_provider' => self::PROVIDER]],
            ['device_info' => ['serial_number' => 'PROVIDER-REJECTED', 'manufacturer' => $unsupported->name, 'vending_provider' => self::PROVIDER]],
        ]]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.imported_count', 1);
        $response->assertJsonPath('data.failed.0.serial_number', 'PROVIDER-REJECTED');
        $this->assertSame(self::PROVIDER, Meter::query()->where('serial_number', 'PROVIDER-OK')->firstOrFail()->vending_provider);
        $this->assertFalse(Meter::query()->where('serial_number', 'PROVIDER-REJECTED')->exists());
    }

    public function testExportIncludesTheProviderSoTheFileCanBeReimported(): void {
        $device = $this->seedMeter(self::SUPPORTED_API_NAME, self::PROVIDER);
        $exportService = resolve(DeviceExportService::class);
        $exportService->setDeviceData(Device::query()->whereKey($device->id)->get());

        $exported = $exportService->exportDataToArray();

        $this->assertSame(self::PROVIDER, $exported[0]['device_info']['vending_provider']);
    }

    private function seedMeter(string $manufacturerApiName, ?string $vendingProvider): Device {
        $manufacturer = Manufacturer::query()->firstWhere('api_name', $manufacturerApiName)
            ?? ManufacturerFactory::new()->create(['type' => 'meter', 'api_name' => $manufacturerApiName]);
        $meter = MeterFactory::new()->create([
            'manufacturer_id' => $manufacturer->id,
            'meter_type_id' => MeterTypeFactory::new()->create()->id,
            'tariff_id' => TariffFactory::new()->create(['price' => 100])->id,
            'serial_number' => str_random(12),
            'vending_provider' => $vendingProvider,
        ]);

        return DeviceFactory::new()->create([
            'person_id' => PersonFactory::new()->create()->id,
            'device_id' => $meter->id,
            'device_type' => Meter::RELATION_NAME,
            'device_serial' => $meter->serial_number,
        ]);
    }

    /**
     * @param array<string, mixed> $changes
     */
    private function updateMeter(Device $device, array $changes): TestResponse {
        return $this->actingAs($this->user)->putJson("/api/meters/{$device->device_id}", ['id' => $device->device_id, ...$changes]);
    }

    /**
     * @param list<string> $manufacturerApiNames
     */
    private function registerProvider(string $key, int $mpmPluginId, array $manufacturerApiNames): IVendingProvider {
        $api = $this->fakeApi(
            [ManufacturerCapability::CreditToken, ManufacturerCapability::ResetToken],
            'PROVIDER-TOKEN',
        );
        $this->app->instance($api::class, $api);

        resolve(VendingProviderRegistry::class)->register(new VendingProvider(
            key: $key,
            name: $key === self::PROVIDER ? 'Test provider' : $key,
            apiClass: $api::class,
            mpmPluginId: $mpmPluginId,
            manufacturerApiNames: $manufacturerApiNames,
        ));

        return $api;
    }

    private function disableProviderPlugin(): void {
        Plugins::query()->where('mpm_plugin_id', MpmPlugin::CALIN_METER)->update(['status' => Plugins::INACTIVE]);
    }

    private function payment(Device $device): Transaction {
        return TransactionFactory::new()->create([
            'message' => $device->device_serial,
            'amount' => 1000,
            'original_transaction_type' => CashTransaction::RELATION_NAME,
            'original_transaction_id' => CashTransaction::query()->create(['user_id' => $this->user->id, 'status' => 1])->id,
        ]);
    }

    private function bindOemApi(string $apiName): void {
        $api = $this->fakeApi([ManufacturerCapability::CreditToken], 'OEM-TOKEN');
        $this->app->bind($apiName, fn () => $api);
    }

    /**
     * @param list<ManufacturerCapability> $capabilities
     */
    private function fakeApi(array $capabilities, string $token): IVendingProvider {
        return new class($capabilities, $token) implements IVendingProvider {
            public bool $configured = false;

            /**
             * @param list<ManufacturerCapability> $capabilities
             */
            public function __construct(private array $capabilities, private string $token) {}

            public function isConfiguredFor(Meter $meter): bool {
                return $this->configured;
            }

            public function capabilities(): array {
                return $this->capabilities;
            }

            public function chargeDevice(TransactionDataContainer $transactionContainer): array {
                return [
                    'token' => $this->token,
                    'token_type' => Token::TYPE_ENERGY,
                    'token_unit' => Token::UNIT_KWH,
                    'token_amount' => 10.0,
                ];
            }

            public function unlockDevice(TransactionDataContainer $transactionContainer): array {
                return [];
            }

            public function clearDevice(Device $device): ?array {
                return null;
            }
        };
    }
}
