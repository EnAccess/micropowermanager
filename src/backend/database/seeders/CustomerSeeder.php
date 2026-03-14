<?php

namespace Database\Seeders;

use App\Models\Address\Address;
use App\Models\Village;
use App\Models\GeographicalInformation;
use App\Models\Person\Person;
use App\Services\DatabaseProxyManagerService;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder {
    public function __construct(
        private DatabaseProxyManagerService $databaseProxyManagerService,
    ) {
        $this->databaseProxyManagerService->buildDatabaseConnectionDemoCompany();
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        // Get available Villages
        $villages = Village::all();

        // For each Village generate customers
        foreach ($villages as $village) {
            Person::factory()
                ->count(50)
                ->isCustomer()
                ->has(
                    Address::factory()
                        ->for($village)
                        ->has(
                            GeographicalInformation::factory()
                                // https://github.com/larastan/larastan/issues/2307
                                // @phpstan-ignore argument.type
                                ->state(function (array $attributes, Address $address) {
                                    return ['points' => $address->village->location->points];
                                })
                                ->randomizePointsInVillage(),
                            'geo'
                        )
                )
                ->create();
        }
    }
}
