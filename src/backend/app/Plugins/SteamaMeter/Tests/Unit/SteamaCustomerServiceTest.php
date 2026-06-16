<?php

namespace App\Plugins\SteamaMeter\Tests\Unit;

use App\Models\City;
use App\Models\MiniGrid;
use App\Plugins\SteamaMeter\Models\SteamaSite;
use App\Plugins\SteamaMeter\Services\SteamaCustomerService;
use Database\Factories\ClusterFactory;
use Database\Factories\UserFactory;
use Tests\TestCase;

class SteamaCustomerServiceTest extends TestCase {
    private const int SITE_ID = 77;

    protected function setUp(): void {
        parent::setUp();

        $cluster = ClusterFactory::new()->create(['manager_id' => UserFactory::new()->create()->id]);
        $miniGrid = MiniGrid::query()->create(['name' => 'Test Grid', 'cluster_id' => $cluster->id]);
        City::query()->create(['name' => 'Test City', 'mini_grid_id' => $miniGrid->id, 'country_id' => 0]);
        SteamaSite::query()->create([
            'site_id' => self::SITE_ID,
            'mpm_mini_grid_id' => $miniGrid->id,
            'hash' => 'hash',
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function customerPayload(array $overrides = []): array {
        return array_merge([
            'id' => 1001,
            'first_name' => 'Ada',
            'last_name' => 'Eze',
            'telephone' => '+255712345678',
            'site' => self::SITE_ID,
            'site_name' => 'Test City',
        ], $overrides);
    }

    public function testCreateRelatedPersonStoresNullForAnUnparseablePhone(): void {
        $person = resolve(SteamaCustomerService::class)->createRelatedPerson(
            $this->customerPayload(['telephone' => '+'])
        );

        $address = $person->addresses()->where('is_primary', 1)->first();
        $this->assertNotNull($address);
        $this->assertNull($address->phone);
    }

    public function testCreateRelatedPersonNormalizesAValidPhoneToE164(): void {
        $person = resolve(SteamaCustomerService::class)->createRelatedPerson(
            $this->customerPayload(['telephone' => '+255 712 345 678'])
        );

        $address = $person->addresses()->where('is_primary', 1)->first();
        $this->assertEquals('+255712345678', $address->phone);
    }
}
