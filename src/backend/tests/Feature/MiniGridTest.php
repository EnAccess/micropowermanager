<?php

namespace Tests\Feature;

use App\Models\GeographicalInformation;
use App\Models\MiniGrid;
use Database\Factories\CityFactory;
use Database\Factories\ClusterFactory;
use Database\Factories\MiniGridFactory;
use Database\Factories\UserFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class MiniGridTest extends TestCase {
    use CreateEnvironments;

    private $user;
    private $person;
    private array $clusterIds = [];
    private array $miniGridIds = [];

    public function testUserGetsMiniGridList(): void {
        $clusterCount = 1;
        $miniGridCount = 2;
        $this->createTestData($clusterCount, $miniGridCount);
        $response = $this->actingAs($this->user)->get('/api/mini-grids');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), count($this->miniGridIds));
    }

    public function testUserGetsMiniGridById(): void {
        $clusterCount = 1;
        $miniGridCount = 2;
        $this->createTestData($clusterCount, $miniGridCount);
        $response = $this->actingAs($this->user)->get(sprintf('/api/mini-grids/%s', $this->miniGridIds[0]));
        $response->assertStatus(200);
        $this->assertEquals($response['data']['id'], $this->miniGridIds[0]);
    }

    public function testUserGetsMiniGridByIdWithGeographicalInformation(): void {
        $clusterCount = 1;
        $miniGridCount = 2;
        $this->createTestData($clusterCount, $miniGridCount);
        $response = $this->actingAs($this->user)->get(sprintf('/api/mini-grids/%s?relation=1', $this->miniGridIds[0]));
        $response->assertStatus(200);
        $this->assertEquals(array_key_exists('location', $response['data']), true);
    }

    public function testUserCreatesNewMiniGrid(): void {
        $clusterCount = 1;
        $miniGridCount = 0;
        $this->createTestData($clusterCount, $miniGridCount);
        $miGridData = [
            'cluster_id' => $this->clusterIds[0],
            'name' => $this->faker->name(),
            'geo_data' => $this->faker->latitude().','.$this->faker->longitude(),
        ];
        $response = $this->actingAs($this->user)->post('/api/mini-grids', $miGridData);
        $response->assertStatus(201);
        $this->assertEquals($response['data']['name'], $miGridData['name']);
        $this->assertEquals(count(MiniGrid::query()->get()), 1);
    }

    protected function createTestData($clusterCount = 1, $miniGridCount = 1) {
        $this->user = UserFactory::new()->create();
        $this->user->syncRoles('admin');

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
                $geographicalInformation->owner()->associate($miniGrid);
                $geographicalInformation->save();
                CityFactory::new()->create([
                    'name' => $this->faker->citySuffix().$this->faker->randomAscii(),
                    'country_id' => 1,
                    'mini_grid_id' => $miniGrid->id,
                    'cluster_id' => $cluster->id,
                ]);
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
