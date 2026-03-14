<?php

namespace Tests\Feature;

use App\Models\GeographicalInformation;
use App\Models\MiniGrid;
use Database\Factories\VillageFactory;
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
        $miniGridList = $this->getResponseCollection($response);
        $returnedMiniGridIds = array_values(array_filter(array_column($miniGridList, 'id'), 'is_int'));

        foreach ($this->miniGridIds as $miniGridId) {
            $this->assertContains($miniGridId, $returnedMiniGridIds);
        }
    }

    public function testUserGetsMiniGridById(): void {
        $clusterCount = 1;
        $miniGridCount = 2;
        $this->createTestData($clusterCount, $miniGridCount);
        $response = $this->actingAs($this->user)->get(sprintf('/api/mini-grids/%s', $this->miniGridIds[0]));
        $response->assertStatus(200);
        $miniGridPayload = $this->getResponsePayload($response);
        $this->assertEquals($this->miniGridIds[0], $miniGridPayload['id']);
    }

    public function testUserGetsMiniGridByIdWithGeographicalInformation(): void {
        $clusterCount = 1;
        $miniGridCount = 2;
        $this->createTestData($clusterCount, $miniGridCount);
        $response = $this->actingAs($this->user)->get(sprintf('/api/mini-grids/%s?relation=1', $this->miniGridIds[0]));
        $response->assertStatus(200);
        $miniGridPayload = $this->getResponsePayload($response);
        $this->assertEquals(array_key_exists('location', $miniGridPayload), true);
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
        $this->assertContains($response->status(), [200, 201]);
        $miniGridPayload = $this->getResponsePayload($response);
        $this->assertEquals($miGridData['name'], $miniGridPayload['name']);
        $this->assertTrue(
            MiniGrid::query()->where('name', $miGridData['name'])->where('cluster_id', $this->clusterIds[0])->exists()
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getResponseCollection(mixed $response): array {
        /** @var mixed $json */
        $json = $response->json();

        if (!is_array($json)) {
            return [];
        }

        if (array_key_exists('data', $json) && is_array($json['data']) && array_is_list($json['data'])) {
            /** @var array<int, array<string, mixed>> $data */
            $data = $json['data'];

            return $data;
        }

        if (array_is_list($json)) {
            /** @var array<int, array<string, mixed>> $json */
            return $json;
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function getResponsePayload(mixed $response): array {
        /** @var mixed $json */
        $json = $response->json();

        if (!is_array($json)) {
            return [];
        }

        if (array_key_exists('data', $json) && is_array($json['data']) && !array_is_list($json['data'])) {
            /** @var array<string, mixed> $data */
            $data = $json['data'];

            return $data;
        }

        /** @var array<string, mixed> $json */
        return $json;
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
                VillageFactory::new()->create([
                    'name' => $this->faker->streetName().$this->faker->randomAscii(),
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
