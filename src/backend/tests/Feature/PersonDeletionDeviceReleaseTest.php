<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Meter\Meter;
use Database\Factories\Meter\MeterFactory;
use Database\Factories\Person\PersonFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class PersonDeletionDeviceReleaseTest extends TestCase {
    use CreateEnvironments;

    public function testDeletingCustomerReleasesDeviceInsteadOfDeletingIt(): void {
        $this->createTestData();

        $person = PersonFactory::new()->create();
        $meter = MeterFactory::new()->create();
        $device = Device::factory()
            ->for($person)
            ->for($meter, 'device')
            ->createOne(['device_serial' => $meter->serial_number]);

        $response = $this->actingAs($this->user)->deleteJson("/api/people/{$person->id}");
        $response->assertStatus(200);

        $this->assertSoftDeleted($person);

        $device->refresh();
        $this->assertNull($device->person_id, 'The device must be unassigned, not deleted.');
        $this->assertTrue(
            Device::query()->whereKey($device->id)->exists(),
            'The device row must survive customer deletion so it can be reassigned.'
        );
        $this->assertTrue(
            Meter::query()->whereKey($meter->id)->exists(),
            'The underlying meter must survive customer deletion.'
        );
    }
}
