<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\ApplianceType;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\Person\PersonFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class ApplianceSoftDeleteTest extends TestCase {
    use CreateEnvironments;

    public function testDeletingApplianceSoftDeletesIt(): void {
        $this->createTestData();
        $appliance = $this->createAppliance();

        $this->actingAs($this->user)->delete("/api/appliances/{$appliance->id}")->assertStatus(200);

        $this->assertNull(Appliance::query()->find($appliance->id));
        $this->assertNotNull(Appliance::query()->withTrashed()->find($appliance->id)?->deleted_at);

        $listedIds = collect($this->actingAs($this->user)->get('/api/appliances')->json('data'))->pluck('id');
        $this->assertNotContains($appliance->id, $listedIds);
    }

    public function testSoldAppliancesStillShowDeletedAppliance(): void {
        $this->createTestData();
        $appliance = $this->createAppliance();
        $person = PersonFactory::new()->create();
        /** @var AppliancePerson $appliancePerson */
        $appliancePerson = AppliancePersonFactory::new()->create([
            'appliance_id' => $appliance->id,
            'person_id' => $person->id,
            'total_cost' => 1000,
            'rate_count' => 5,
            'down_payment' => 0,
        ]);

        $this->actingAs($this->user)->delete("/api/appliances/{$appliance->id}")->assertStatus(200);

        $response = $this->actingAs($this->user)->get("/api/appliances/person/people/{$person->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $appliancePerson->id);
        $response->assertJsonPath('data.0.appliance.name', 'Retired Appliance');
        $response->assertJsonPath('data.0.appliance.appliance_type.id', $appliance->appliance_type_id);
    }

    public function testDeletedApplianceCannotBeSold(): void {
        $this->createTestData();
        $appliance = $this->createAppliance();
        $person = PersonFactory::new()->create();
        $appliance->delete();

        $this->actingAs($this->user)
            ->post("/api/appliances/person/{$appliance->id}/people/{$person->id}", [])
            ->assertStatus(404);
    }

    private function createAppliance(): Appliance {
        /** @var ApplianceType $applianceType */
        $applianceType = ApplianceTypeFactory::new()->create();

        return Appliance::query()->create([
            'name' => 'Retired Appliance',
            'price' => 1000,
            'appliance_type_id' => $applianceType->id,
        ]);
    }
}
