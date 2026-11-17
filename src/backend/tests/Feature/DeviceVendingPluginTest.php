<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTO\TransactionDataContainer;
use App\Enums\ManufacturerCapability;
use App\Events\PaymentSuccessEvent;
use App\Events\TransactionFailedEvent;
use App\Events\TransactionSuccessfulEvent;
use App\Jobs\TokenProcessor;
use App\Lib\IVendingAPI;
use App\Models\Device;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\MpmPlugin;
use App\Models\Plugins;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use App\Services\CashTransactionService;
use Database\Factories\DeviceFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\Meter\MeterFactory;
use Database\Factories\Meter\MeterTypeFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\TariffFactory;
use Illuminate\Support\Facades\Event;
use Tests\CreateEnvironments;
use Tests\TestCase;

class DeviceVendingPluginTest extends TestCase {
    use CreateEnvironments;

    private const string OEM_API = 'VendingTestOemApi';
    private const string OTHER_OEM_API = 'VendingTestOtherOemApi';
    private const string VENDING_ONLY_API = 'VendingTestPluginApi';

    public function testANewDeviceIsVendedByItsManufacturerPlugin(): void {
        $this->createTestData();
        $oemPlugin = $this->createVendingPlugin(self::OEM_API);

        $device = $this->seedMeter(self::OEM_API);

        $this->assertSame($oemPlugin->id, $device->vending_plugin_id);
    }

    public function testADeviceWhoseManufacturerHasNoPluginHasNoVendingPlugin(): void {
        $this->createTestData();

        $device = $this->seedMeter('VendingTestUnknownApi');

        $this->assertNull($device->vending_plugin_id);
    }

    public function testItListsTheManufacturerPluginAndActiveVendingOnlyPlugins(): void {
        $this->createTestData();
        $oemPlugin = $this->createVendingPlugin(self::OEM_API);
        $vendingOnlyPlugin = $this->createActiveVendingPlugin(self::VENDING_ONLY_API);
        $this->createVendingPlugin('VendingTestInactiveApi');
        $this->createActiveVendingPlugin(self::OTHER_OEM_API);
        ManufacturerFactory::new()->create(['type' => 'meter', 'api_name' => self::OTHER_OEM_API]);
        $device = $this->seedMeter(self::OEM_API);

        $response = $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/vending-plugins");

        $response->assertStatus(200);
        $response->assertJsonPath('data.vending_plugin_id', $oemPlugin->id);
        $this->assertEqualsCanonicalizing(
            [$oemPlugin->id, $vendingOnlyPlugin->id],
            array_column($response->json('data.options'), 'id'),
        );
    }

    public function testItMovesADeviceToAVendingOnlyPlugin(): void {
        $this->createTestData();
        $this->createVendingPlugin(self::OEM_API);
        $vendingOnlyPlugin = $this->createActiveVendingPlugin(self::VENDING_ONLY_API);
        $device = $this->seedMeter(self::OEM_API);

        $response = $this->actingAs($this->user)->putJson(
            "/api/devices/{$device->id}/vending-plugin",
            ['vending_plugin_id' => $vendingOnlyPlugin->id],
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.vending_plugin_id', $vendingOnlyPlugin->id);
        $this->assertSame($vendingOnlyPlugin->id, $device->fresh()->vending_plugin_id);
    }

    public function testItRejectsAPluginThatCannotVendTheDevice(): void {
        $this->createTestData();
        $oemPlugin = $this->createVendingPlugin(self::OEM_API);
        $otherOemPlugin = $this->createActiveVendingPlugin(self::OTHER_OEM_API);
        ManufacturerFactory::new()->create(['type' => 'meter', 'api_name' => self::OTHER_OEM_API]);
        $device = $this->seedMeter(self::OEM_API);

        $response = $this->actingAs($this->user)->putJson(
            "/api/devices/{$device->id}/vending-plugin",
            ['vending_plugin_id' => $otherOemPlugin->id],
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('vending_plugin_id');
        $this->assertSame($oemPlugin->id, $device->fresh()->vending_plugin_id);
    }

    public function testNullMovesTheDeviceBackToItsManufacturerPlugin(): void {
        $this->createTestData();
        $oemPlugin = $this->createVendingPlugin(self::OEM_API);
        $vendingOnlyPlugin = $this->createActiveVendingPlugin(self::VENDING_ONLY_API);
        $device = $this->seedMeter(self::OEM_API);
        $device->update(['vending_plugin_id' => $vendingOnlyPlugin->id]);

        $response = $this->actingAs($this->user)->putJson(
            "/api/devices/{$device->id}/vending-plugin",
            ['vending_plugin_id' => null],
        );

        $response->assertStatus(200);
        $this->assertSame($oemPlugin->id, $device->fresh()->vending_plugin_id);
    }

    public function testAPaymentIsVendedByTheDevicesVendingPlugin(): void {
        $this->createTestData();
        $this->createVendingPlugin(self::OEM_API);
        $vendingOnlyPlugin = $this->createActiveVendingPlugin(self::VENDING_ONLY_API);
        $this->bindVendingApi(self::OEM_API, 'OEM-TOKEN');
        $this->bindVendingApi(self::VENDING_ONLY_API, 'PLUGIN-TOKEN');
        $device = $this->seedMeter(self::OEM_API);
        $device->update(['vending_plugin_id' => $vendingOnlyPlugin->id]);
        Event::fake([TransactionSuccessfulEvent::class, PaymentSuccessEvent::class]);

        $this->processPayment($device);

        $this->assertSame('PLUGIN-TOKEN', Token::query()->where('device_id', $device->id)->sole()->token);
    }

    public function testAPaymentFailsWhenTheVendingPluginIsInactive(): void {
        $this->createTestData();
        $this->createVendingPlugin(self::OEM_API);
        $vendingOnlyPlugin = $this->createActiveVendingPlugin(self::VENDING_ONLY_API);
        $this->bindVendingApi(self::OEM_API, 'OEM-TOKEN');
        $this->bindVendingApi(self::VENDING_ONLY_API, 'PLUGIN-TOKEN');
        $device = $this->seedMeter(self::OEM_API);
        $device->update(['vending_plugin_id' => $vendingOnlyPlugin->id]);
        Plugins::query()->where('mpm_plugin_id', $vendingOnlyPlugin->id)->update(['status' => Plugins::INACTIVE]);
        Event::fake([TransactionFailedEvent::class]);

        $this->processPayment($device);

        $this->assertFalse(Token::query()->where('device_id', $device->id)->exists());
        Event::assertDispatched(
            TransactionFailedEvent::class,
            fn (TransactionFailedEvent $event): bool => str_contains($event->message, $vendingOnlyPlugin->name),
        );
    }

    public function testDisablingAPluginHandsItsDevicesBackToTheirManufacturerPlugin(): void {
        $this->createTestData();
        $oemPlugin = $this->createVendingPlugin(self::OEM_API);
        $vendingOnlyPlugin = $this->createActiveVendingPlugin(self::VENDING_ONLY_API);
        $device = $this->seedMeter(self::OEM_API);
        $device->update(['vending_plugin_id' => $vendingOnlyPlugin->id]);

        $response = $this->actingAs($this->user)->putJson("/api/plugins/{$vendingOnlyPlugin->id}", ['checked' => false]);

        $response->assertStatus(200);
        $this->assertSame($oemPlugin->id, $device->fresh()->vending_plugin_id);
    }

    public function testChangingTheManufacturerHandsTheDeviceToTheNewManufacturersPlugin(): void {
        $this->createTestData();
        $this->createVendingPlugin(self::OEM_API);
        $otherOemPlugin = $this->createVendingPlugin(self::OTHER_OEM_API);
        $otherManufacturer = ManufacturerFactory::new()->create(['type' => 'meter', 'api_name' => self::OTHER_OEM_API]);
        $device = $this->seedMeter(self::OEM_API);

        $device->device->update(['manufacturer_id' => $otherManufacturer->id]);

        $this->assertSame($otherOemPlugin->id, $device->fresh()->vending_plugin_id);
    }

    public function testChangingTheManufacturerKeepsAVendingOnlyPlugin(): void {
        $this->createTestData();
        $this->createVendingPlugin(self::OEM_API);
        $this->createVendingPlugin(self::OTHER_OEM_API);
        $vendingOnlyPlugin = $this->createActiveVendingPlugin(self::VENDING_ONLY_API);
        $otherManufacturer = ManufacturerFactory::new()->create(['type' => 'meter', 'api_name' => self::OTHER_OEM_API]);
        $device = $this->seedMeter(self::OEM_API);
        $device->update(['vending_plugin_id' => $vendingOnlyPlugin->id]);

        $device->device->update(['manufacturer_id' => $otherManufacturer->id]);

        $this->assertSame($vendingOnlyPlugin->id, $device->fresh()->vending_plugin_id);
    }

    private function createActiveVendingPlugin(string $vendingApi): MpmPlugin {
        $mpmPlugin = $this->createVendingPlugin($vendingApi);
        Plugins::query()->create(['mpm_plugin_id' => $mpmPlugin->id, 'status' => Plugins::ACTIVE]);

        return $mpmPlugin;
    }

    private function seedMeter(string $apiName): Device {
        $manufacturer = Manufacturer::query()->where('api_name', $apiName)->first()
            ?? ManufacturerFactory::new()->create(['type' => 'meter', 'api_name' => $apiName]);
        $meter = MeterFactory::new()->create([
            'manufacturer_id' => $manufacturer->id,
            'meter_type_id' => MeterTypeFactory::new()->create()->id,
            'tariff_id' => TariffFactory::new()->create(['price' => 100])->id,
            'serial_number' => str_random(12),
        ]);

        return DeviceFactory::new()->create([
            'person_id' => PersonFactory::new()->create()->id,
            'device_id' => $meter->id,
            'device_type' => Meter::RELATION_NAME,
            'device_serial' => $meter->serial_number,
        ]);
    }

    private function bindVendingApi(string $alias, string $token): void {
        $this->app->bind($alias, fn () => new class($token) implements IVendingAPI {
            public function __construct(private string $token) {}

            public function capabilities(): array {
                return [ManufacturerCapability::CreditToken];
            }

            public function chargeDevice(TransactionDataContainer $transactionContainer): array {
                return [
                    'token' => $this->token,
                    'token_type' => Token::TYPE_ENERGY,
                    'token_unit' => Token::UNIT_KWH,
                    'token_amount' => 1.0,
                ];
            }

            public function unlockDevice(TransactionDataContainer $transactionContainer): array {
                return [];
            }

            public function clearDevice(Device $device): ?array {
                return null;
            }
        });
    }

    private function processPayment(Device $device): void {
        $transaction = resolve(CashTransactionService::class)->createTransaction(
            $this->user->id,
            100,
            '-',
            $device->device_serial,
            Transaction::TYPE_DEFERRED_PAYMENT,
        );

        new TokenProcessor($this->companyId, TransactionDataContainer::initialize($transaction))->handle();
    }
}
