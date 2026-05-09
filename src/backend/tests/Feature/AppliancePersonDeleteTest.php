<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use App\Models\Device;
use App\Models\Log;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\Person\PersonFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class AppliancePersonDeleteTest extends TestCase {
    use CreateEnvironments;

    public function testSoftDeletesAppliancePerson(): void {
        $this->createTestData();
        $appliancePerson = $this->seedAppliance();

        $response = $this->actingAs($this->user)->delete(
            "/api/appliances/person/{$appliancePerson->id}",
            ['admin_id' => $this->user->id]
        );

        $response->assertStatus(200);
        $this->assertNull(AppliancePerson::query()->find($appliancePerson->id));
        $this->assertNotNull(AppliancePerson::query()->withTrashed()->find($appliancePerson->id));
    }

    public function testReleasesBoundDevice(): void {
        $this->createTestData();
        $appliancePerson = $this->seedAppliance(deviceSerial: 'SN-TEST-1');
        Device::query()->create([
            'device_serial' => 'SN-TEST-1',
            'person_id' => $appliancePerson->person_id,
            'device_type' => Device::class,
            'device_id' => 0,
        ]);

        $this->actingAs($this->user)->delete(
            "/api/appliances/person/{$appliancePerson->id}",
            ['admin_id' => $this->user->id]
        )->assertStatus(200);

        $device = Device::query()->where('device_serial', 'SN-TEST-1')->first();
        $this->assertNotNull($device);
        $this->assertNull($device->person_id);
    }

    public function testAllowsDeleteWhenRatesPaid(): void {
        $this->createTestData();
        $appliancePerson = $this->seedAppliance();
        $appliancePerson->rates()->oldest('due_date')->first()->update(['remaining' => 0]);

        $response = $this->actingAs($this->user)->delete(
            "/api/appliances/person/{$appliancePerson->id}",
            ['admin_id' => $this->user->id]
        );

        $response->assertStatus(200);
        $this->assertNotNull(AppliancePerson::query()->withTrashed()->find($appliancePerson->id)->deleted_at);
    }

    public function testWritesAuditLog(): void {
        $this->createTestData();
        $appliancePerson = $this->seedAppliance();

        $this->actingAs($this->user)->delete(
            "/api/appliances/person/{$appliancePerson->id}",
            ['admin_id' => $this->user->id]
        )->assertStatus(200);

        $log = Log::query()
            ->where('affected_type', AppliancePerson::class)
            ->where('affected_id', $appliancePerson->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($log);
        $this->assertSame($this->user->id, $log->user_id);
        $this->assertStringContainsString("User {$this->user->name} deleted the sold appliance", $log->action);
    }

    public function testShowReturnsTrashedAppliancePersonWithDeletedAt(): void {
        $this->createTestData();
        $appliancePerson = $this->seedAppliance();

        $this->actingAs($this->user)->delete(
            "/api/appliances/person/{$appliancePerson->id}",
            ['admin_id' => $this->user->id]
        )->assertStatus(200);

        $response = $this->actingAs($this->user)->get(
            "/api/appliances/person/people/detail/{$appliancePerson->id}"
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $appliancePerson->id);
        $this->assertNotNull($response->json('data.deleted_at'));
    }

    private function seedAppliance(?string $deviceSerial = null): AppliancePerson {
        $person = PersonFactory::new()->create();
        $applianceType = ApplianceTypeFactory::new()->create();
        $appliance = Appliance::query()->create([
            'name' => 'Test Appliance',
            'price' => 1000,
            'appliance_type_id' => $applianceType->id,
        ]);

        /** @var AppliancePerson $appliancePerson */
        $appliancePerson = AppliancePersonFactory::new()->create([
            'appliance_id' => $appliance->id,
            'person_id' => $person->id,
            'total_cost' => 1000,
            'rate_count' => 5,
            'down_payment' => 0,
            'device_serial' => $deviceSerial,
        ]);

        foreach ([200, 200, 200, 200, 200] as $i => $cost) {
            ApplianceRate::query()->create([
                'appliance_person_id' => $appliancePerson->id,
                'rate_cost' => $cost,
                'remaining' => $cost,
                'remind' => 0,
                'due_date' => now()->addMonths($i + 1),
            ]);
        }

        return $appliancePerson->fresh();
    }
}
