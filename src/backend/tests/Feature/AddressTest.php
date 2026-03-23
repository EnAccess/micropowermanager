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
        $this->createCity(1);

        $response = $this->actingAs($user)->post(sprintf('/api/people/%s/addresses', $person->id), [
            'email' => $this->faker->email(),
            'phone' => $this->faker->e164PhoneNumber(),
            'street' => $this->faker->streetAddress(),
            'city_id' => $this->city->id,
            'country_id' => 1,
            'cluster_id' => $this->cluster->id,
            'mini_grid_id' => $this->miniGrid->id,
            'primary' => 1,
        ]);
        $response->assertStatus(200);
        $this->assertEquals(1, $person->addresses()->count());
        $this->assertEquals($this->city->id, $person->addresses()->first()->city_id);
        $this->assertEquals(1, $person->addresses()->first()->is_primary);
    }

    public function testCreatingAddressWithNonInternationalPhoneNumberIsNotAllowed(): void {
        $user = UserFactory::new()->create();
        $this->user = $user;
        $this->assignRole($user, 'admin');
        $person = PersonFactory::new()->create();
        $this->createCluster(1);
        $this->createMiniGrid(1);
        $this->createCity(1);

        $response = $this->actingAs($user)->post(sprintf('/api/people/%s/addresses', $person->id), [
            'email' => $this->faker->email(),
            'phone' => '0712345678',
            'street' => $this->faker->streetAddress(),
            'city_id' => $this->city->id,
            'country_id' => 1,
            'cluster_id' => $this->cluster->id,
            'mini_grid_id' => $this->miniGrid->id,
            'primary' => 1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('phone');
    }

    public function testUserUpdatesAndAddressOfCustomerForOwnCompany(): void {
        $user = UserFactory::new()->create();
        $this->user = $user;
        $this->assignRole($user, 'admin');
        $person = PersonFactory::new()->create();
        $this->createCluster(1);
        $this->createMiniGrid(1);
        $this->createCity(1);

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
            'city_id' => $this->city->id,
            'primary' => 0,
        ]);

        $response->assertStatus(200);
        $this->assertEquals($streetName, $person->addresses()->first()->street);
        $this->assertEquals(0, $person->addresses()->first()->is_primary);
    }
}
