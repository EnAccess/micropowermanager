<?php

namespace Tests\Feature;

use App\Models\City;
use Database\Factories\CityFactory;
use Database\Factories\MiniGridFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class CustomerRegistrationAppCitiesTest extends TestCase {
    use CreateEnvironments;

    private const ENDPOINT = '/api/customer-registration-app/cities';

    public function testAgentGetsOnlyTheVillagesOfTheirOwnMiniGrid(): void {
        $this->seedAgent();
        $ownCity = $this->createCityIn($this->agent->mini_grid_id, 'Own Village');
        $foreignCity = $this->createCityIn($this->createForeignMiniGridId(), 'Foreign Village');

        $response = $this->actingAs($this->agent)->getJson(self::ENDPOINT);

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($ownCity->id, $ids);
        $this->assertNotContains($foreignCity->id, $ids);
    }

    public function testAgentWithoutVillagesInTheirMiniGridGetsAnEmptyList(): void {
        $this->seedAgent();
        $this->createCityIn($this->createForeignMiniGridId(), 'Foreign Village');
        $this->agent->update(['mini_grid_id' => $this->createForeignMiniGridId()]);

        $response = $this->actingAs($this->agent)->getJson(self::ENDPOINT);

        $response->assertStatus(200);
        $this->assertSame([], $response->json('data'));
    }

    public function testDefaultPageSizeIsFifteen(): void {
        $this->seedAgent();
        for ($i = 0; $i < 20; ++$i) {
            $this->createCityIn($this->agent->mini_grid_id, sprintf('Village %02d', $i));
        }

        $response = $this->actingAs($this->agent)->getJson(self::ENDPOINT);

        $response->assertStatus(200);
        $response->assertJsonPath('per_page', 15);
        $this->assertCount(15, $response->json('data'));
    }

    public function testEveryVillageIsReachableByWalkingThePages(): void {
        $this->seedAgent();
        $expected = [];
        for ($i = 0; $i < 37; ++$i) {
            $expected[] = $this->createCityIn($this->agent->mini_grid_id, sprintf('Village %02d', $i))->id;
        }

        $collected = [];
        $page = 1;
        do {
            $response = $this->actingAs($this->agent)->getJson(self::ENDPOINT.'?page='.$page);
            $response->assertStatus(200);
            $collected = array_merge($collected, collect($response->json('data'))->pluck('id')->all());
            $lastPage = $response->json('last_page');
            ++$page;
        } while ($page <= $lastPage);

        $this->assertSame(count($collected), count(array_unique($collected)));
        $this->assertEmpty(array_diff($expected, $collected));
    }

    public function testExplicitLimitIsHonoured(): void {
        $this->seedAgent();
        for ($i = 0; $i < 5; ++$i) {
            $this->createCityIn($this->agent->mini_grid_id, sprintf('Village %02d', $i));
        }

        $response = $this->actingAs($this->agent)->getJson(self::ENDPOINT.'?limit=2');

        $response->assertStatus(200);
        $response->assertJsonPath('per_page', 2);
        $this->assertCount(2, $response->json('data'));
    }

    public function testWebUserStillGetsEveryVillageOfTheFirstPage(): void {
        $this->seedAgent();
        $ownCity = $this->createCityIn($this->agent->mini_grid_id, 'Own Village');
        $foreignCity = $this->createCityIn($this->createForeignMiniGridId(), 'Foreign Village');

        $response = $this->actingAs($this->user)->getJson(self::ENDPOINT);

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($ownCity->id, $ids);
        $this->assertContains($foreignCity->id, $ids);
    }

    public function testScopedResponseKeepsTheLegacyPayloadShape(): void {
        $this->seedAgent();
        $this->createCityIn($this->agent->mini_grid_id, 'Own Village');

        $response = $this->actingAs($this->agent)->getJson(self::ENDPOINT);

        $response->assertStatus(200);
        $city = $response->json('data')[0];
        $this->assertArrayHasKey('id', $city);
        $this->assertArrayHasKey('name', $city);
        $this->assertArrayHasKey('mini_grid_id', $city);
        $this->assertArrayHasKey('location', $city);
        $this->assertArrayHasKey('mini_grid', $city);
        $this->assertArrayHasKey('country', $city);
    }

    private function seedAgent(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
    }

    private function createForeignMiniGridId(): int {
        return MiniGridFactory::new()->create([
            'cluster_id' => $this->cluster->id,
            'name' => $this->faker->unique()->companySuffix,
        ])->id;
    }

    private function createCityIn(int $miniGridId, string $name): City {
        return CityFactory::new()->create([
            'name' => $name,
            'country_id' => 1,
            'mini_grid_id' => $miniGridId,
        ]);
    }
}
