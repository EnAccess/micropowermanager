<?php

namespace Database\Seeders;

use App\Models\Address\Address;
use App\Models\Agent;
use App\Models\AgentCharge;
use App\Models\AgentCommission;
use App\Models\GeographicalInformation;
use App\Models\MiniGrid;
use App\Models\Person\Person;
use App\Services\CompanyService;
use Illuminate\Database\Seeder;

class AgentSeeder extends Seeder {
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
        $agentCommission = AgentCommission::factory()
            ->create();

        // Get available MiniGrids
        $minigrids = MiniGrid::all();

        // For each Mini-Grid we create one Agent
        foreach ($minigrids as $minigrid) {
            $village = $minigrid->cities()->get()->random();

            $person = Person::factory()
                ->isAgent($village->name)
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

            $agent = Agent::factory()
                ->for($minigrid)
                ->for($agentCommission, 'commission')
                ->for($person)
                ->state(
                    ['name' => $person->name]
                )
                ->create();

            // Give our Agent some balance
            $agent_charge = AgentCharge::factory()
                ->for($agent)
                ->create();
        }
    }
}
