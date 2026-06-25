<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Address\Address;
use App\Models\Appliance;
use App\Models\Device;
use App\Models\Person\Person;
use App\Models\SolarHomeSystem;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Event;
use Tests\CreateEnvironments;
use Tests\TestCase;

class AppliancePersonDeviceAssignmentTest extends TestCase {
    use CreateEnvironments;

    public function testSellingApplianceWithDeviceKeepsCustomerAddressUntouchedAndStoresDeviceGeo(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        Event::fake();

        $person = PersonFactory::new()->create();
        $primaryAddress = Address::query()->make([
            'street' => 'Existing Street',
            'city_id' => $this->city->id,
            'is_primary' => 1,
        ]);
        $primaryAddress->owner()->associate($person)->save();

        $applianceType = ApplianceTypeFactory::new()->create();
        $appliance = Appliance::query()->create([
            'name' => 'Test Solar Panel',
            'price' => 1000,
            'appliance_type_id' => $applianceType->id,
        ]);

        $manufacturer = ManufacturerFactory::new()->isShsManufacturer()->create();
        $solarHomeSystem = SolarHomeSystem::query()->create([
            'serial_number' => 'SHS-SOLD-0001',
            'manufacturer_id' => $manufacturer->id,
            'appliance_id' => $appliance->id,
        ]);
        $device = Device::query()->create([
            'person_id' => null,
            'device_id' => $solarHomeSystem->id,
            'device_type' => SolarHomeSystem::RELATION_NAME,
            'device_serial' => $solarHomeSystem->serial_number,
        ]);

        $seller = UserFactory::new()->create(['company_id' => $this->companyId]);

        $response = $this->actingAs($this->user)->postJson(
            "/api/appliances/person/{$appliance->id}/people/{$person->id}",
            [
                'id' => $appliance->id,
                'person_id' => $person->id,
                'user_id' => $seller->id,
                'cost' => 1000,
                'rate' => 5,
                'rate_type' => 'monthly',
                'down_payment' => 0,
                'device_serial' => $device->device_serial,
                'points' => '12.34,56.78',
                'address' => [
                    'street' => $primaryAddress->street,
                    'city_id' => $primaryAddress->city_id,
                ],
            ]
        );

        $response->assertStatus(200);

        $this->assertCount(1, Person::query()->findOrFail($person->id)->addresses, 'Selling an appliance must not create a duplicate address.');

        $device->refresh();
        $this->assertSame($person->id, $device->person_id);
        $this->assertSame('12.34,56.78', $device->geo?->points);
    }
}
