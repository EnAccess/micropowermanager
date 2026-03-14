<?php

namespace Tests\Feature;

use App\Models\Village;
use App\Models\GeographicalInformation;
use Database\Factories\VillageFactory;
use Database\Factories\ClusterFactory;
use Database\Factories\MiniGridFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class VillageTest extends TestCase {
    use RefreshMultipleDatabases;
    use WithFaker;

    private $user;
    private $person;
    private array $clusterIds = [];
    private array $miniGridIds = [];
    private array $villageIds = [];

    public function testUserGetsVillages(): void {
        $clusterCount = 1;
        $miniGridCount = 1;
        $this->createTestData($clusterCount, $miniGridCount);
        $response = $this->actingAs($this->user)->get('/api/villages');
        $response->assertStatus(200);
        $villageList = $this->getResponseCollection($response);
        $returnedVillageIds = array_values(array_filter(array_column($villageList, 'id'), 'is_int'));

        foreach ($this->villageIds as $villageId) {
            $this->assertContains($villageId, $returnedVillageIds);
        }
    }

    public function testUserGetsVillageById(): void {
        $clusterCount = 1;
        $miniGridCount = 1;
        $villageCount = 1;
        $this->createTestData($clusterCount, $miniGridCount, $villageCount);
        $response = $this->actingAs($this->user)->get(sprintf('/api/villages/%s', $this->villageIds[0]));
        $response->assertStatus(200);
        $villagePayload = $this->getResponsePayload($response);
        $this->assertEquals($this->villageIds[0], $villagePayload['id']);
    }

    public function testUserCreatesNewVillage(): void {
        $clusterCount = 1;
        $miniGridCount = 1;
        $villageCount = 1;
        $this->createTestData($clusterCount, $miniGridCount, $villageCount);
        $villageData = [
            'mini_grid_id' => $this->miniGridIds[0],
            'country_id' => 1,
            'points' => '-7.873645,39.754433',
            'name' => $this->faker->streetName(),
        ];
        $response = $this->actingAs($this->user)->post('/api/villages', $villageData);
        $this->assertContains($response->status(), [200, 201]);
        $villagePayload = $this->getResponsePayload($response);
        $this->assertEquals($villageData['name'], $villagePayload['name']);
    }

    public function testUserUpdatesAVillage(): void {
        $clusterCount = 2;
        $miniGridCount = 2;
        $this->createTestData($clusterCount, $miniGridCount);
        $village = Village::query()->first();
        $villageData = [
            'name' => 'updatedName',
            'mini_grid_id' => $this->miniGridIds[1],
            'country_id' => 1,
            'points' => '-7.873645,39.754433',
        ];
        $response = $this->actingAs($this->user)->put(sprintf('/api/villages/%s', $village->id), $villageData);
        $response->assertStatus(200);
        $villagePayload = $this->getResponsePayload($response);
        $this->assertEquals($villageData['name'], $villagePayload['name']);
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

    protected function createTestData($clusterCount = 1, $miniGridCount = 1, $villageCount = 1) {
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

                while ($villageCount > 0) {
                    $village = VillageFactory::new()->create([
                        'name' => $this->faker->unique()->streetName(),
                        'country_id' => 1,
                        'mini_grid_id' => $miniGrid->id,
                    ]);
                    $this->villageIds[] = $village->id;
                    --$villageCount;
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
