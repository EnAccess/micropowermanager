<?php

namespace Tests\Feature;

use App\Models\GeographicalInformation;
use App\Models\MiniGrid;
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

class MiniGridTest extends TestCase {
    use RefreshMultipleDatabases;
    use WithFaker;

    private $user;
    private $company;
    private $companyDatabase;
    private $person;
    private $clusterIds = [];
    private $miniGridIds = [];

    public function testUserGetsMiniGridList() {
        $clusterCount = 1;
        $miniGridCount = 2;
        $this->createTestData($clusterCount, $miniGridCount);
        $response = $this->actingAs($this->user)->get('/api/mini-grids');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), count($this->miniGridIds));
    }

    public function testUserGetsMiniGridById() {
        $clusterCount = 1;
        $miniGridCount = 2;
        $this->createTestData($clusterCount, $miniGridCount);
        $response = $this->actingAs($this->user)->get(sprintf('/api/mini-grids/%s', $this->miniGridIds[0]));
        $response->assertStatus(200);
        $this->assertEquals($response['data']['id'], $this->miniGridIds[0]);
    }

    public function testUserGetsMiniGridByIdWithGeographicalInformation() {
        $clusterCount = 1;
        $miniGridCount = 2;
        $this->createTestData($clusterCount, $miniGridCount);
        $response = $this->actingAs($this->user)->get(sprintf('/api/mini-grids/%s?relation=1', $this->miniGridIds[0]));
        $response->assertStatus(200);
        $this->assertEquals(array_key_exists('location', $response['data']), true);
    }

    public function testUserCreatesNewMiniGrid() {
        $clusterCount = 1;
        $miniGridCount = 0;
        $this->createTestData($clusterCount, $miniGridCount);
        $miGridData = [
            'cluster_id' => $this->clusterIds[0],
            'name' => $this->faker->name,
            'geo_data' => [
                'latitude' => $this->faker->latitude,
                'longitude' => $this->faker->longitude,
            ],
        ];
        $response = $this->actingAs($this->user)->post('/api/mini-grids', $miGridData);
        $response->assertStatus(201);
        $this->assertEquals($response['data']['name'], $miGridData['name']);
        $this->assertEquals(count(MiniGrid::query()->get()), 1);
    }

    public function testUserUpdatesAMiniGrid() {
        $clusterCount = 1;
        $miniGridCount = 1;
        $this->createTestData($clusterCount, $miniGridCount);
        $miniGrid = MiniGrid::query()->first();
        $miGridData = [
            'name' => 'updatedName',
        ];
        $response = $this->actingAs($this->user)->put(sprintf('/api/mini-grids/%s', $miniGrid->id), $miGridData);
        $response->assertStatus(200);
        $this->assertEquals($response['data']['name'], $miGridData['name']);
    }

    protected function createTestData($clusterCount = 1, $miniGridCount = 1) {
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
                $geographicalInformation->owner()->associate($miniGrid);
                $geographicalInformation->save();
                $city = CityFactory::new()->create([
                    'name' => $this->faker->unique()->citySuffix,
                    'country_id' => 1,
                    'mini_grid_id' => $miniGrid->id,
                    'cluster_id' => $cluster->id,
                ]);
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
