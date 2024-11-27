<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\GeographicalInformation;
use Database\Factories\CityFactory;
use Database\Factories\ClusterFactory;
use Database\Factories\CompanyDatabaseFactory;
use Database\Factories\CompanyFactory;
use Database\Factories\MiniGridFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CityTest extends TestCase {
    use RefreshMultipleDatabases;
    use WithFaker;

    private $user;
    private $company;
    private $companyDatabase;
    private $person;
    private $clusterIds = [];
    private $miniGridIds = [];
    private $cityIds = [];

    public function testUserGetsCities() {
        $clusterCount = 1;
        $miniGridCount = 1;
        $cityCount = 5;
        $this->createTestData($clusterCount, $miniGridCount);
        $response = $this->actingAs($this->user)->get('/api/cities');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), count($this->cityIds));
    }

    public function testUserGetsCityById() {
        $clusterCount = 1;
        $miniGridCount = 1;
        $cityCount = 1;
        $this->createTestData($clusterCount, $miniGridCount, $cityCount);
        $response = $this->actingAs($this->user)->get(sprintf('/api/cities/%s', $this->cityIds[0]));
        $response->assertStatus(200);
        $this->assertEquals($response['data']['id'], $this->cityIds[0]);
    }

    public function testUserCreatesNewCity() {
        $clusterCount = 1;
        $miniGridCount = 1;
        $cityCount = 1;
        $this->createTestData($clusterCount, $miniGridCount, $cityCount);
        $cityData = [
            'cluster_id' => $this->clusterIds[0],
            'mini_grid_id' => $this->miniGridIds[0],
            'name' => $this->faker->city,
        ];
        $response = $this->actingAs($this->user)->post('/api/cities', $cityData);
        $response->assertStatus(201);
        $this->assertEquals($response['data']['name'], $cityData['name']);
    }

    public function testUserUpdatesACity() {
        $clusterCount = 2;
        $miniGridCount = 2;
        $cityCount = 1;
        $this->createTestData($clusterCount, $miniGridCount);
        $city = City::query()->first();
        $cityData = [
            'name' => 'updatedName',
            'mini_grid_id' => $this->miniGridIds[1],
            'cluster_id' => $this->clusterIds[1],
        ];
        $response = $this->actingAs($this->user)->put(sprintf('/api/cities/%s', $city->id), $cityData);
        $response->assertStatus(200);
        $this->assertEquals($response['data']['name'], $cityData['name']);
    }

    protected function createTestData($clusterCount = 1, $miniGridCount = 1, $cityCount = 1) {
        $this->user = UserFactory::new()->create();
        $this->company = CompanyFactory::new()->create();
        $this->companyDatabase = CompanyDatabaseFactory::new()->create();

        while ($clusterCount > 0) {
            $user = UserFactory::new()->create();
            $cluster = ClusterFactory::new()->create([
                'name' => $this->faker->unique()->companySuffix,
                'manager_id' => $this->user->id,
            ]);
            array_push($this->clusterIds, $cluster->id);

            while ($miniGridCount > 0) {
                $geographicalInformation = GeographicalInformation::query()->make(['points' => '111,222']);
                $miniGrid = MiniGridFactory::new()->create([
                    'cluster_id' => $cluster->id,
                    'name' => $this->faker->unique()->companySuffix,
                ]);

                while ($cityCount > 0) {
                    $city = CityFactory::new()->create([
                        'name' => $this->faker->unique()->citySuffix,
                        'country_id' => 1,
                        'mini_grid_id' => $miniGrid->id,
                        'cluster_id' => $cluster->id,
                    ]);
                    array_push($this->cityIds, $city->id);
                    --$cityCount;
                }

                $geographicalInformation->owner()->associate($miniGrid);
                $geographicalInformation->save();
                array_push($this->miniGridIds, $miniGrid->id);
                --$miniGridCount;
            }

            --$clusterCount;
        }
    }

    public function actingAs($user, $driver = null) {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }

    protected function generateUniqueNumber(): int {
        return $this->faker->unique()->randomNumber() + $this->faker->unique()->randomNumber() +
            $this->faker->unique()->randomNumber();
    }
}
