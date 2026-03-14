<?php

namespace Tests\Feature;

use Database\Factories\Address\AddressFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\CreateEnvironments;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class AddressTest extends TestCase {
    use RefreshMultipleDatabases;
    use WithFaker;
    use CreateEnvironments;

    public function testUserDefinesAnAddressToCustomerForOwnCompany(): void {
        $user = UserFactory::new()->create();
        $this->user = $user;
        $this->assignRole($user, 'admin');
        $person = PersonFactory::new()->create();
        $this->createCluster(1);
        $this->createMiniGrid(1);
        $this->createVillage(1);

        $response = $this->actingAs($user)->post(sprintf('/api/people/%s/addresses', $person->id), [
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'street' => $this->faker->streetAddress(),
            'village_id' => $this->village->id,
            'country_id' => 1,
            'cluster_id' => $this->cluster->id,
            'mini_grid_id' => $this->miniGrid->id,
            'primary' => 1,
        ]);
        $response->assertStatus(200);
        $this->assertEquals(1, $person->addresses()->count());
        $this->assertEquals($this->village->id, $person->addresses()->first()->village_id);
        $this->assertEquals(1, $person->addresses()->first()->is_primary);
    }

    public function testUserUpdatesAndAddressOfCustomerForOwnCompany(): void {
        $user = UserFactory::new()->create();
        $this->user = $user;
        $this->assignRole($user, 'admin');
        $person = PersonFactory::new()->create();
        $this->createCluster(1);
        $this->createMiniGrid(1);
        $this->createVillage(1);

        $address = AddressFactory::new()->make();
        $address->owner()->associate($person);
        $address->save();

        $streetName = $this->faker->streetName();

        $response = $this->actingAs($user)->put(sprintf('/api/people/%s/addresses', $person->id), [
            'id' => $address->id,
            'street' => $streetName,
            'country_id' => 1,
            'cluster_id' => $this->cluster->id,
            'mini_grid_id' => $this->miniGrid->id,
            'village_id' => $this->village->id,
            'primary' => 0,
        ]);

        $response->assertStatus(200);
        $this->assertEquals($streetName, $person->addresses()->first()->street);
        $this->assertEquals(0, $person->addresses()->first()->is_primary);
    }
}
