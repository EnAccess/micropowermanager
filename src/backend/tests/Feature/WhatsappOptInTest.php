<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Factories\Person\PersonFactory;
use Database\Factories\UserFactory;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class WhatsappOptInTest extends TestCase {
    use RefreshMultipleDatabases;

    private User $user;

    protected function setUp(): void {
        parent::setUp();
        $this->user = UserFactory::new()->create(['company_id' => $this->companyId]);
        $this->assignRole($this->user, 'admin');
        $this->assignPermission($this->user, 'customers');
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function testDefaultsToNotOptedInWhenNoRecordExists(): void {
        $person = PersonFactory::new()->create();

        $response = $this->actingAs($this->user)->getJson('/api/whatsapp-business-api/opt-in/'.$person->id);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'person_id' => $person->id,
                'opted_in' => false,
            ],
        ]);
    }

    public function testUpdatesOptInStatusToTrue(): void {
        $person = PersonFactory::new()->create();

        $response = $this->actingAs($this->user)->putJson('/api/whatsapp-business-api/opt-in/'.$person->id, [
            'opted_in' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'person_id' => $person->id,
                'opted_in' => true,
            ],
        ]);

        $this->assertDatabaseHas('whatsapp_business_opt_ins', [
            'person_id' => $person->id,
            'opted_in' => 1,
        ], 'tenant');

        $this->assertDatabaseHas('whatsapp_business_opt_in_histories', [
            'person_id' => $person->id,
            'opted_in' => 1,
            'actor_type' => 'user',
            'actor_id' => $this->user->id,
            'source' => 'admin_ui',
        ], 'tenant');
    }

    public function testUpdatesOptInStatusToFalse(): void {
        $person = PersonFactory::new()->create();

        // First opt in
        $this->actingAs($this->user)->putJson('/api/whatsapp-business-api/opt-in/'.$person->id, [
            'opted_in' => true,
        ]);

        // Then opt out
        $response = $this->actingAs($this->user)->putJson('/api/whatsapp-business-api/opt-in/'.$person->id, [
            'opted_in' => false,
            'note' => 'Customer requested opt-out over phone',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'person_id' => $person->id,
                'opted_in' => false,
            ],
        ]);

        $this->assertDatabaseHas('whatsapp_business_opt_ins', [
            'person_id' => $person->id,
            'opted_in' => 0,
        ], 'tenant');

        $this->assertDatabaseHas('whatsapp_business_opt_in_histories', [
            'person_id' => $person->id,
            'opted_in' => 0,
            'actor_type' => 'user',
            'actor_id' => $this->user->id,
            'source' => 'admin_ui',
            'note' => 'Customer requested opt-out over phone',
        ], 'tenant');
    }

    public function testRejectsInvalidOptInPayload(): void {
        $person = PersonFactory::new()->create();

        $response = $this->actingAs($this->user)->putJson('/api/whatsapp-business-api/opt-in/'.$person->id, [
            'opted_in' => 'not-a-boolean',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['opted_in']);
    }

    public function testBulkOptInAllCustomers(): void {
        $p1 = PersonFactory::new()->isCustomer()->create();
        $p2 = PersonFactory::new()->isCustomer()->create();
        $p3 = PersonFactory::new()->isCustomer()->create();

        $response = $this->actingAs($this->user)->postJson('/api/whatsapp-business-api/opt-in/bulk', [
            'opted_in' => true,
            'opt_in_all' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'count' => 3,
                'opted_in' => true,
            ],
        ]);

        $this->assertDatabaseHas('whatsapp_business_opt_ins', ['person_id' => $p1->id, 'opted_in' => 1], 'tenant');
        $this->assertDatabaseHas('whatsapp_business_opt_ins', ['person_id' => $p2->id, 'opted_in' => 1], 'tenant');
        $this->assertDatabaseHas('whatsapp_business_opt_ins', ['person_id' => $p3->id, 'opted_in' => 1], 'tenant');
    }

    public function testBulkOptInSpecificCustomerIds(): void {
        $p1 = PersonFactory::new()->isCustomer()->create();
        $p2 = PersonFactory::new()->isCustomer()->create();
        $p3 = PersonFactory::new()->isCustomer()->create();

        $response = $this->actingAs($this->user)->postJson('/api/whatsapp-business-api/opt-in/bulk', [
            'opted_in' => true,
            'person_ids' => [$p1->id, $p2->id],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'count' => 2,
                'opted_in' => true,
            ],
        ]);

        $this->assertDatabaseHas('whatsapp_business_opt_ins', ['person_id' => $p1->id, 'opted_in' => 1], 'tenant');
        $this->assertDatabaseHas('whatsapp_business_opt_ins', ['person_id' => $p2->id, 'opted_in' => 1], 'tenant');
        $this->assertDatabaseMissing('whatsapp_business_opt_ins', ['person_id' => $p3->id], 'tenant');
    }

    public function testListCustomersWithOptInStatus(): void {
        $p1 = PersonFactory::new()->isCustomer()->create(['name' => 'Alice', 'surname' => 'Wonderland']);
        $p2 = PersonFactory::new()->isCustomer()->create(['name' => 'Bob', 'surname' => 'Builder']);

        $this->actingAs($this->user)->putJson('/api/whatsapp-business-api/opt-in/'.$p1->id, ['opted_in' => true]);

        $response = $this->actingAs($this->user)->getJson('/api/whatsapp-business-api/opt-in');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'phone', 'opted_in', 'updated_at'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);

        // Filter by status=opted_in
        $optedInResponse = $this->actingAs($this->user)->getJson('/api/whatsapp-business-api/opt-in?status=opted_in');
        $optedInResponse->assertStatus(200);
        $this->assertTrue(collect($optedInResponse->json('data'))->contains('id', $p1->id));
        $this->assertFalse(collect($optedInResponse->json('data'))->contains('id', $p2->id));
    }

    public function testGetOptInStats(): void {
        $p1 = PersonFactory::new()->isCustomer()->create();
        $p2 = PersonFactory::new()->isCustomer()->create();

        $this->actingAs($this->user)->putJson('/api/whatsapp-business-api/opt-in/'.$p1->id, ['opted_in' => true]);
        $this->actingAs($this->user)->putJson('/api/whatsapp-business-api/opt-in/'.$p2->id, ['opted_in' => false]);

        $response = $this->actingAs($this->user)->getJson('/api/whatsapp-business-api/opt-in/stats');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'total_customers' => 2,
                'opted_in' => 1,
                'opted_out' => 1,
                'pending' => 0,
            ],
        ]);
    }

    public function testDirectOptInDuringCustomerRegistration(): void {
        $user = UserFactory::new()->create(['company_id' => $this->companyId]);
        $this->assignRole($user, 'admin');
        $this->assignPermission($user, 'customers');
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $cluster = \Database\Factories\ClusterFactory::new()->create(['manager_id' => $user->id]);
        $miniGrid = \Database\Factories\MiniGridFactory::new()->create(['cluster_id' => $cluster->id]);
        $city = \Database\Factories\CityFactory::new()->create([
            'country_id' => 1,
            'mini_grid_id' => $miniGrid->id,
        ]);

        $response = $this->actingAs($user)->postJson('/api/people', [
            'name' => 'Alice',
            'surname' => 'Smith',
            'phone' => '+255712345678',
            'city_id' => $city->id,
            'street' => 'Kilimanjaro Rd',
            'whatsapp_opt_in' => true,
        ]);

        $response->assertStatus(201);
        $createdId = $response->json('data.id');
        $this->assertDatabaseHas('whatsapp_business_opt_ins', [
            'person_id' => $createdId,
            'opted_in' => 1,
        ], 'tenant');

        $this->assertDatabaseHas('whatsapp_business_opt_in_histories', [
            'person_id' => $createdId,
            'opted_in' => 1,
            'source' => 'customer_registration',
        ], 'tenant');
    }
}
