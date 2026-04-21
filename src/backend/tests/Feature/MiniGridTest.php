<?php

namespace Tests\Feature;

use App\Exceptions\EntityHasChildrenException;
use App\Models\City;
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

    public function testUserUpdatesMiniGrid(): void {
        $this->createTestData(1, 1);
        $miniGrid = MiniGrid::query()->find($this->miniGridIds[0]);

        $response = $this->actingAs($this->user)->put("/api/mini-grids/{$miniGrid->id}", [
            'name' => 'updatedMiniGridName',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('updatedMiniGridName', $response['data']['name']);
        $this->assertEquals('updatedMiniGridName', $miniGrid->fresh()->name);
    }

    public function testUserSoftDeletesChildlessMiniGrid(): void {
        $this->createTestData(1, 1);
        // createTestData adds a city — delete it first so the mini-grid is childless.
        City::query()->where('mini_grid_id', $this->miniGridIds[0])->delete();

        $response = $this->actingAs($this->user)->delete("/api/mini-grids/{$this->miniGridIds[0]}");

        $response->assertStatus(200);
        $this->assertNull(MiniGrid::query()->find($this->miniGridIds[0]));
        $this->assertNotNull(MiniGrid::withTrashed()->find($this->miniGridIds[0])->deleted_at);
    }

    public function testMiniGridDeleteBlockedWhenItHasCities(): void {
        $this->createTestData(1, 1);

        $this->withoutExceptionHandling();
        $this->expectException(EntityHasChildrenException::class);

        try {
            $this->actingAs($this->user)->delete("/api/mini-grids/{$this->miniGridIds[0]}");
        } finally {
            $this->assertNotNull(MiniGrid::query()->find($this->miniGridIds[0]));
        }
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
