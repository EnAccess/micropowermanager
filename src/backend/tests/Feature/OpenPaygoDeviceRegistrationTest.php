<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\Device;
use App\Models\Manufacturer;
use App\Models\Person\Person;
use App\Plugins\OpenPaygo\Models\OpenPaygoDeviceConfiguration;
use Database\Factories\ApplianceFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\Person\PersonFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class OpenPaygoDeviceRegistrationTest extends TestCase {
    use CreateEnvironments;

    public function testItRegistersAndConfiguresAnOpenPaygoDeviceForASelectedCustomer(): void {
        $this->createTestData();
        $customer = PersonFactory::new()->isCustomer()->create();
        $manufacturer = ManufacturerFactory::new()->isShsManufacturer()->create([
            'name' => 'OpenPAYGO',
            'api_name' => 'OpenPaygoApi',
        ]);
        $applianceType = ApplianceType::query()->findOrFail(1);
        $appliance = ApplianceFactory::new()->create(['appliance_type_id' => $applianceType->id]);
        $secretKey = '0123456789abcdef0123456789abcdef';

        $response = $this->actingAs($this->user)->postJson('/api/devices/openpaygo', [
            'serial_number' => 'MPMOPENPAYGO01',
            'manufacturer_id' => $manufacturer->id,
            'appliance_id' => $appliance->id,
            'person_id' => $customer->id,
            'secretKeyHex' => $secretKey,
            'startingCode' => 321,
            'nextCounter' => 0,
            'timeDivider' => 4,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.serial_number', 'MPMOPENPAYGO01')
            ->assertJsonPath('data.manufacturer_id', $manufacturer->id)
            ->assertJsonPath('data.device.person.id', $customer->id);

        $device = Device::query()->where('device_serial', 'MPMOPENPAYGO01')->firstOrFail();
        $configuration = OpenPaygoDeviceConfiguration::query()
            ->where('device_id', $device->id)
            ->firstOrFail();

        $this->assertSame($customer->id, $device->person_id);
        $this->assertNotSame($secretKey, $configuration->getRawOriginal('secret_key_hex'));
        $this->assertSame(321, $configuration->starting_code);
        $this->assertSame(0, $configuration->next_counter);
        $this->assertSame(4, $configuration->time_divider);
        $this->assertInstanceOf(Appliance::class, $device->device->appliance);
        $this->assertInstanceOf(Person::class, $device->person);
    }

    public function testItRejectsDuplicateSerialNumbers(): void {
        $this->createTestData();
        $customer = PersonFactory::new()->isCustomer()->create();
        $manufacturer = ManufacturerFactory::new()->isShsManufacturer()->create([
            'name' => 'OpenPAYGO',
            'api_name' => 'OpenPaygoApi',
        ]);
        $applianceType = ApplianceType::query()->findOrFail(1);
        $appliance = ApplianceFactory::new()->create(['appliance_type_id' => $applianceType->id]);
        $serialNumber = 'MPMOPENPAYGO02';
        $this->actingAs($this->user)->postJson('/api/devices/openpaygo', [
            'serial_number' => $serialNumber,
            'manufacturer_id' => $manufacturer->id,
            'appliance_id' => $appliance->id,
            'person_id' => $customer->id,
            'secretKeyHex' => '0123456789abcdef0123456789abcdef',
            'startingCode' => 321,
            'nextCounter' => 0,
            'timeDivider' => 4,
        ])->assertCreated();

        $duplicate = $this->actingAs($this->user)->postJson('/api/devices/openpaygo', [
            'serial_number' => $serialNumber,
            'manufacturer_id' => $manufacturer->id,
            'appliance_id' => $appliance->id,
            'person_id' => $customer->id,
            'secretKeyHex' => '0123456789abcdef0123456789abcdef',
            'startingCode' => 321,
            'nextCounter' => 0,
            'timeDivider' => 4,
        ]);

        $duplicate->assertUnprocessable()
            ->assertJsonValidationErrors(['serial_number']);
        $this->assertSame(1, Device::query()->where('device_serial', $serialNumber)->count());
        $this->assertSame(1, Manufacturer::query()->where('api_name', 'OpenPaygoApi')->count());
    }

    public function testItRejectsMissingAndInvalidProvisioningValuesBeforeCreatingADevice(): void {
        $this->createTestData();
        $customer = PersonFactory::new()->isCustomer()->create();
        $manufacturer = ManufacturerFactory::new()->isShsManufacturer()->create([
            'name' => 'OpenPAYGO',
            'api_name' => 'OpenPaygoApi',
        ]);
        $applianceType = ApplianceType::query()->findOrFail(1);
        $appliance = ApplianceFactory::new()->create(['appliance_type_id' => $applianceType->id]);

        $missingValues = $this->actingAs($this->user)->postJson('/api/devices/openpaygo', [
            'serial_number' => 'MPMOPENPAYGO03',
            'manufacturer_id' => $manufacturer->id,
            'appliance_id' => $appliance->id,
            'person_id' => $customer->id,
        ]);
        $missingValues->assertUnprocessable()
            ->assertJsonValidationErrors([
                'secretKeyHex',
                'startingCode',
                'nextCounter',
                'timeDivider',
            ]);

        $response = $this->actingAs($this->user)->postJson('/api/devices/openpaygo', [
            'serial_number' => 'MPMOPENPAYGO03',
            'manufacturer_id' => $manufacturer->id,
            'appliance_id' => $appliance->id,
            'person_id' => $customer->id,
            'secretKeyHex' => 'not-hex',
            'startingCode' => -1,
            'nextCounter' => 100001,
            'timeDivider' => 0,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'secretKeyHex',
                'startingCode',
                'nextCounter',
                'timeDivider',
            ]);
        $this->assertDatabaseMissing('devices', ['device_serial' => 'MPMOPENPAYGO03'], 'tenant');
    }
}
