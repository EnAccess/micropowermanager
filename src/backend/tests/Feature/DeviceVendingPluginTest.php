<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTO\TransactionDataContainer;
use App\Enums\MpmPlugin;
use App\Enums\VendingCapability;
use App\Events\PaymentSuccessEvent;
use App\Events\TransactionFailedEvent;
use App\Events\TransactionSuccessfulEvent;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Jobs\TokenProcessor;
use App\Jobs\VerifyDeviceMappingJob;
use App\Lib\ITokenIssuer;
use App\Models\Device;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Plugins;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use App\Plugins\CalinMeter\CalinMeterApi;
use App\Services\CashTransactionService;
use Database\Factories\DeviceFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\Meter\MeterFactory;
use Database\Factories\Meter\MeterTypeFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\TariffFactory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\CreateEnvironments;
use Tests\TestCase;

class DeviceVendingPluginTest extends TestCase {
    use CreateEnvironments;

    private const string OEM_API = 'CalinMeterApi';
    private const string OTHER_OEM_API = 'KelinMeterApi';

    public function testANewDeviceIsVendedByItsManufacturerPlugin(): void {
        $this->createTestData();

        $device = $this->seedMeter(self::OEM_API);

        $this->assertSame(MpmPlugin::CalinMeter, $device->vending_plugin);
    }

    public function testADeviceWhoseManufacturerHasNoPluginHasNoVendingPlugin(): void {
        $this->createTestData();

        $device = $this->seedMeter('VendingTestUnknownApi');

        $this->assertNull($device->vending_plugin);
    }

    public function testItListsTheManufacturerPluginAndActiveVendingOnlyPlugins(): void {
        $this->createTestData();
        $vendingOnlyPlugin = $this->createVendingPlugin(VendingTestPluginApi::class);
        $this->activatePlugin(MpmPlugin::KelinMeter);
        ManufacturerFactory::new()->create(['type' => 'meter', 'api_name' => self::OTHER_OEM_API]);
        $device = $this->seedMeter(self::OEM_API);

        $response = $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/vending-plugins");

        $response->assertStatus(200);
        $response->assertJsonPath('data.vending_plugin', MpmPlugin::CalinMeter->value);
        $this->assertEqualsCanonicalizing(
            [MpmPlugin::CalinMeter->value, $vendingOnlyPlugin->value],
            array_column($response->json('data.options'), 'slug'),
        );
    }

    public function testItLeavesOutAnInactiveVendingOnlyPlugin(): void {
        $this->createTestData();
        $this->createVendingPlugin(VendingTestPluginApi::class, active: false);
        $device = $this->seedMeter(self::OEM_API);

        $response = $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/vending-plugins");

        $response->assertStatus(200);
        $this->assertSame([MpmPlugin::CalinMeter->value], array_column($response->json('data.options'), 'slug'));
    }

    public function testItMovesADeviceToAVendingOnlyPlugin(): void {
        $this->createTestData();
        $vendingOnlyPlugin = $this->createVendingPlugin(VendingTestPluginApi::class);
        $device = $this->seedMeter(self::OEM_API);
        Queue::fake();

        $response = $this->actingAs($this->user)->putJson(
            "/api/devices/{$device->id}/vending-plugin",
            ['vending_plugin' => $vendingOnlyPlugin->value],
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.vending_plugin', $vendingOnlyPlugin->value);
        $this->assertSame($vendingOnlyPlugin, $device->fresh()->vending_plugin);
        Queue::assertPushed(VerifyDeviceMappingJob::class);
    }

    public function testItRejectsAPluginThatCannotVendTheDevice(): void {
        $this->createTestData();
        $this->activatePlugin(MpmPlugin::KelinMeter);
        ManufacturerFactory::new()->create(['type' => 'meter', 'api_name' => self::OTHER_OEM_API]);
        $device = $this->seedMeter(self::OEM_API);

        $response = $this->actingAs($this->user)->putJson(
            "/api/devices/{$device->id}/vending-plugin",
            ['vending_plugin' => MpmPlugin::KelinMeter->value],
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('vending_plugin');
        $this->assertSame(MpmPlugin::CalinMeter, $device->fresh()->vending_plugin);
    }

    public function testNullMovesTheDeviceBackToItsManufacturerPlugin(): void {
        $this->createTestData();
        $vendingOnlyPlugin = $this->createVendingPlugin(VendingTestPluginApi::class);
        $device = $this->seedMeter(self::OEM_API);
        $device->update(['vending_plugin' => $vendingOnlyPlugin]);

        $response = $this->actingAs($this->user)->putJson(
            "/api/devices/{$device->id}/vending-plugin",
            ['vending_plugin' => null],
        );

        $response->assertStatus(200);
        $this->assertSame(MpmPlugin::CalinMeter, $device->fresh()->vending_plugin);
    }

    public function testAPaymentIsVendedByTheManufacturerPlugin(): void {
        $this->createTestData();
        $this->activatePlugin(MpmPlugin::CalinMeter);
        $this->bindOemApi();
        $device = $this->seedMeter(self::OEM_API);
        Event::fake([TransactionSuccessfulEvent::class, PaymentSuccessEvent::class]);

        $this->processPayment($device);

        $this->assertSame('OEM-TOKEN', Token::query()->where('device_id', $device->id)->sole()->token);
    }

    public function testAPaymentIsVendedByTheDevicesVendingPlugin(): void {
        $this->createTestData();
        $this->activatePlugin(MpmPlugin::CalinMeter);
        $this->bindOemApi();
        $vendingOnlyPlugin = $this->createVendingPlugin(VendingTestPluginApi::class);
        $device = $this->seedMeter(self::OEM_API);
        $device->update(['vending_plugin' => $vendingOnlyPlugin]);
        Event::fake([TransactionSuccessfulEvent::class, PaymentSuccessEvent::class]);

        $this->processPayment($device);

        $this->assertSame('PLUGIN-TOKEN', Token::query()->where('device_id', $device->id)->sole()->token);
    }

    public function testAPaymentFailsWhenTheVendingPluginIsInactive(): void {
        $this->createTestData();
        $vendingOnlyPlugin = $this->createVendingPlugin(VendingTestPluginApi::class);
        $device = $this->seedMeter(self::OEM_API);
        $device->update(['vending_plugin' => $vendingOnlyPlugin]);
        Plugins::query()->where('mpm_plugin', $vendingOnlyPlugin)->update(['status' => Plugins::INACTIVE]);
        Event::fake([TransactionFailedEvent::class]);

        $this->processPayment($device);

        $this->assertPaymentFailedWith($device, "The vending provider {$vendingOnlyPlugin->displayName()} of device {$device->device_serial} is not active.");
    }

    public function testAPaymentFailsWhenTheManufacturerPluginIsInactive(): void {
        $this->createTestData();
        $this->bindOemApi();
        $device = $this->seedMeter(self::OEM_API);
        Event::fake([TransactionFailedEvent::class]);

        $this->processPayment($device);

        $calinPluginName = MpmPlugin::CalinMeter->displayName();
        $this->assertPaymentFailedWith($device, "The vending provider {$calinPluginName} of device {$device->device_serial} is not active.");
    }

    public function testAPaymentFailsWhenTheVendingPluginIsNotInstalled(): void {
        $this->createTestData();
        $uninstalledPlugin = MpmPlugin::Prospect;
        $this->activatePlugin($uninstalledPlugin);
        $device = $this->seedMeter(self::OEM_API);
        $device->update(['vending_plugin' => $uninstalledPlugin]);
        Event::fake([TransactionFailedEvent::class]);

        $this->processPayment($device);

        $this->assertPaymentFailedWith($device, "The vending provider {$uninstalledPlugin->displayName()} of device {$device->device_serial} is not installed.");
    }

    public function testCapabilitiesReportWhyTheVendingPluginIsUnavailable(): void {
        $this->createTestData();
        $this->bindOemApi();
        $device = $this->seedMeter(self::OEM_API);

        $response = $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/capabilities");

        $calinPluginName = MpmPlugin::CalinMeter->displayName();
        $response->assertStatus(200);
        $response->assertJsonPath('data.token_types', []);
        $response->assertJsonPath(
            'data.token_generation_blocked_reason',
            "The vending provider {$calinPluginName} of device {$device->device_serial} is not active.",
        );
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

    private function bindOemApi(): void {
        $this->app->instance(CalinMeterApi::class, new class extends VendingTestPluginApi {
            protected string $token = 'OEM-TOKEN';
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

    private function assertPaymentFailedWith(Device $device, string $message): void {
        $this->assertFalse(Token::query()->where('device_id', $device->id)->exists());
        Event::assertDispatched(
            TransactionFailedEvent::class,
            fn (TransactionFailedEvent $event): bool => $event->message === $message,
        );
    }
}

class VendingTestPluginApi implements ITokenIssuer {
    protected string $token = 'PLUGIN-TOKEN';

    public function capabilities(): array {
        return [VendingCapability::AddCredit];
    }

    public function addCredit(TransactionDataContainer $transactionContainer): array {
        return [
            'token' => $this->token,
            'token_type' => Token::TYPE_ENERGY,
            'token_unit' => Token::UNIT_KWH,
            'token_amount' => 1.0,
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
}
