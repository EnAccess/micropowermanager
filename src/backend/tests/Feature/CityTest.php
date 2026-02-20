<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\GeographicalInformation;
use Database\Factories\CityFactory;
use Database\Factories\ClusterFactory;
use Database\Factories\MiniGridFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class CityTest extends TestCase {
    use RefreshMultipleDatabases;
    use WithFaker;

    private $user;
    private $person;
    private array $clusterIds = [];
    private array $miniGridIds = [];
    private array $cityIds = [];

    public function testUserGetsCities(): void {
        $clusterCount = 1;
        $miniGridCount = 1;
        $this->createTestData($clusterCount, $miniGridCount);
        $response = $this->actingAs($this->user)->get('/api/cities');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), count($this->cityIds));
    }

    public function testUserGetsCityById(): void {
        $clusterCount = 1;
        $miniGridCount = 1;
        $cityCount = 1;
        $this->createTestData($clusterCount, $miniGridCount, $cityCount);
        $response = $this->actingAs($this->user)->get(sprintf('/api/cities/%s', $this->cityIds[0]));
        $response->assertStatus(200);
        $this->assertEquals($response['data']['id'], $this->cityIds[0]);
    }

    public function testUserCreatesNewCity(): void {
        $clusterCount = 1;
        $miniGridCount = 1;
        $cityCount = 1;
        $this->createTestData($clusterCount, $miniGridCount, $cityCount);
        $cityData = [
            'cluster_id' => $this->clusterIds[0],
            'mini_grid_id' => $this->miniGridIds[0],
            'country_id' => 1,
            'points' => '-7.873645,39.754433',
            'name' => $this->faker->city(),
        ];
        $response = $this->actingAs($this->user)->post('/api/cities', $cityData);
        $response->assertStatus(201);
        $this->assertEquals($response['data']['name'], $cityData['name']);
    }

    public function testUserUpdatesACity(): void {
        $clusterCount = 2;
        $miniGridCount = 2;
        $this->createTestData($clusterCount, $miniGridCount);
        $city = City::query()->first();
        $cityData = [
            'name' => 'updatedName',
            'mini_grid_id' => $this->miniGridIds[1],
            'cluster_id' => $this->clusterIds[1],
            'country_id' => 1,
            'points' => '-7.873645,39.754433',
        ];
        $response = $this->actingAs($this->user)->put(sprintf('/api/cities/%s', $city->id), $cityData);
        $response->assertStatus(200);
        $this->assertEquals($response['data']['name'], $cityData['name']);
    }

    protected function createTestData($clusterCount = 1, $miniGridCount = 1, $cityCount = 1) {
        $this->user = UserFactory::new()->create();
        $this->assignRole($this->user, 'admin');

        while ($clusterCount > 0) {
            $cluster = ClusterFactory::new()->create([
                'name' => $this->faker->unique()->companySuffix(),
                'manager_id' => $this->user->id,
            ]);
            $this->clusterIds[] = $cluster->id;

            while ($miniGridCount > 0) {
                $geographicalInformation = GeographicalInformation::query()->make(['points' => '111,222']);
                $miniGrid = MiniGridFactory::new()->create([
                    'cluster_id' => $cluster->id,
                    'name' => $this->faker->unique()->companySuffix(),
                ]);

                while ($cityCount > 0) {
                    $city = CityFactory::new()->create([
                        'name' => $this->faker->unique()->citySuffix(),
                        'country_id' => 1,
                        'mini_grid_id' => $miniGrid->id,
                        'cluster_id' => $cluster->id,
                    ]);
                    $this->cityIds[] = $city->id;
                    --$cityCount;
                }

                $geographicalInformation->owner()->associate($miniGrid);
                $geographicalInformation->save();
                $this->miniGridIds[] = $miniGrid->id;
                --$miniGridCount;
            }

            --$clusterCount;
        }
    }

    protected function generateUniqueNumber(): int {
        return $this->faker->unique()->randomNumber() + $this->faker->unique()->randomNumber() +
            $this->faker->unique()->randomNumber();
    }
}
