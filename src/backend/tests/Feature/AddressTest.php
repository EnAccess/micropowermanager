<?php

namespace Tests\Feature;

use Database\Factories\AddressFactory;
use Database\Factories\CityFactory;
use Database\Factories\CompanyDatabaseFactory;
use Database\Factories\CompanyFactory;
use Database\Factories\PersonFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AddressTest extends TestCase {
    use RefreshMultipleDatabases;
    use WithFaker;

    public function testUserDefinesAnAddressToCustomerForOwnCompany() {
        $user = UserFactory::new()->create();
        $person = PersonFactory::new()->create();
        $city = CityFactory::new()->create();
        $company = CompanyFactory::new()->create();
        $companyDatabase = CompanyDatabaseFactory::new()->create();

        $response = $this->actingAs($user)->post(sprintf('/api/people/%s/addresses', $person->id), [
            'email' => $this->faker->email,
            'phone' => $this->faker->phoneNumber,
            'street' => $this->faker->streetAddress,
            'city_id' => 1,
            'country_id' => 1,
            'cluster_id' => 1,
            'mini_grid_id' => 1,
            'primary' => 1,
        ]);
        $response->assertStatus(200);
        $this->assertEquals(1, $person->addresses()->count());
        $this->assertEquals($city->id, $person->addresses()->first()->city_id);
        $this->assertEquals(1, $person->addresses()->first()->is_primary);
    }

    public function testUserUpdatesAndAddressOfCustomerForOwnCompany() {
        $user = UserFactory::new()->create();
        $person = PersonFactory::new()->create();
        $city = CityFactory::new()->create();

        $company = CompanyFactory::new()->create();
        $companyDatabase = CompanyDatabaseFactory::new()->create();
        $address = AddressFactory::new()->create();

        $streetName = $this->faker->streetName;

        $response = $this->actingAs($user)->put(sprintf('/api/people/%s/addresses', $person->id), [
            'id' => $address->id,
            'street' => $streetName,
            'country_id' => 1,
            'cluster_id' => 1,
            'mini_grid_id' => 1,
            'city_id' => 1,
            'primary' => 0,
        ]);

        $response->assertStatus(200);
        $this->assertEquals($streetName, $person->addresses()->first()->street);
        $this->assertEquals(0, $person->addresses()->first()->is_primary);
    }

    public function actingAs($user, $driver = null) {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
