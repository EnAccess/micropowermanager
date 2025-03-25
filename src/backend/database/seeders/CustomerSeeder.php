<?php

namespace Database\Seeders;

use App\Models\Address\Address;
use App\Models\City;
use App\Models\GeographicalInformation;
use App\Models\Person\Person;
use App\Services\CompanyService;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder {
    public function __construct(
        private CompanyService $companyService,
    ) {
        $this->companyService->buildDatabaseConnectionDemoCompany();
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        // Get available Villages
        $villages = City::all();

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
                                ->state(function (array $attributes, Address $address) {
                                    return ['points' => $address->city->location->points];
                                })
                                ->randomizePointsInVillage(),
                            'geo'
                        )
                )
                ->create();
        }
    }
}
