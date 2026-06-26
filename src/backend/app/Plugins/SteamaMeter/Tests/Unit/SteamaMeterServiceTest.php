<?php

namespace App\Plugins\SteamaMeter\Tests\Unit;

use App\Models\City;
use App\Models\Meter\Meter;
use App\Models\MiniGrid;
use App\Models\Person\Person;
use App\Plugins\SteamaMeter\Models\SteamaCustomer;
use App\Plugins\SteamaMeter\Models\SteamaTariff;
use App\Plugins\SteamaMeter\Models\SteamaUserType;
use App\Plugins\SteamaMeter\Services\SteamaMeterService;
use Database\Factories\ClusterFactory;
use Database\Factories\ConnectionTypeFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\TariffFactory;
use Database\Factories\UserFactory;
use Tests\TestCase;

class SteamaMeterServiceTest extends TestCase {
    private const int CUSTOMER_ID = 55;

    private function service(): SteamaMeterService {
        return resolve(SteamaMeterService::class);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function meterPayload(array $overrides = []): array {
        return array_merge([
            'reference' => '0023210016244',
            'customer' => self::CUSTOMER_ID,
            'latitude' => '4.95',
            'longitude' => '8.32',
            'version' => 'v1',
            'usage_spike_threshold' => 10,
        ], $overrides);
    }

    public function testCreateRelatedMeterSkipsWhenCustomerIsNotSynced(): void {
        $meter = $this->service()->createRelatedMeter(
            $this->meterPayload(['reference' => 'NO-CUSTOMER', 'customer' => 999999])
        );

        $this->assertNull($meter);
        $this->assertDatabaseMissing('meters', ['serial_number' => 'NO-CUSTOMER'], 'tenant');
    }

    public function testCreateRelatedMeterCreatesAMeterWithItsConnectionTypeAndDevice(): void {
        $connectionType = ConnectionTypeFactory::new()->create();
        $userType = SteamaUserType::query()->create([
            'name' => 'Household',
            'syntax' => 'HH',
            'mpm_connection_type_id' => $connectionType->id,
        ]);
        ManufacturerFactory::new()->create(['name' => 'Steama Meters']);
        SteamaTariff::query()->create(['mpm_tariff_id' => TariffFactory::new()->create()->id]);

        $cluster = ClusterFactory::new()->create(['manager_id' => UserFactory::new()->create()->id]);
        $miniGrid = MiniGrid::query()->create(['name' => 'Test Grid', 'cluster_id' => $cluster->id]);
        $city = City::query()->create(['name' => 'Test City', 'mini_grid_id' => $miniGrid->id, 'country_id' => 0]);

        $person = Person::query()->create(['name' => 'Ada', 'surname' => 'Eze', 'is_customer' => 1]);
        $address = $person->addresses()->make(['phone' => '+255712345678', 'is_primary' => 1, 'city_id' => $city->id]);
        $address->save();
        SteamaCustomer::query()->create([
            'site_id' => 1,
            'user_type_id' => $userType->id,
            'customer_id' => self::CUSTOMER_ID,
            'mpm_customer_id' => $person->id,
            'energy_price' => 1,
            'account_balance' => 0,
            'low_balance_warning' => 0,
        ]);

        $meter = $this->service()->createRelatedMeter($this->meterPayload());

        $this->assertInstanceOf(Meter::class, $meter);
        $this->assertEquals($connectionType->id, $meter->connection_type_id);
        $this->assertGreaterThan(0, $meter->connection_group_id);
        $this->assertNotNull($meter->device);
        $this->assertEquals([4.95, 8.32], $meter->device->geo->latitudeLongitude());
    }
}
