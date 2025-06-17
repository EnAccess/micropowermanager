<?php

namespace Database\Seeders;

use App\Models\Address\Address;
use App\Models\Agent;
use App\Models\AgentCharge;
use App\Models\AgentCommission;
use App\Models\GeographicalInformation;
use App\Models\MiniGrid;
use App\Models\Person\Person;
use App\Services\CompanyDatabaseService;
use App\Services\CompanyService;
use App\Services\DatabaseProxyService;
use App\Utils\DemoCompany;
use Illuminate\Database\Seeder;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class AgentSeeder extends Seeder {
    public function __construct(
        private DatabaseProxyManagerService $databaseProxyManagerService,
        private DatabaseProxyService $databaseProxyService,
        private CompanyService $companyService,
        private CompanyDatabaseService $companyDatabaseService,
    ) {
        $this->databaseProxyManagerService->buildDatabaseConnectionDemoCompany();
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

        $firstAgent = true; // Flag to ensure one agent gets the test email

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

            // Create Agent user and DatabaseProxy
            if ($firstAgent) {
                $company = $this->companyService->getByName(DemoCompany::DEMO_COMPANY_NAME);
                $companyId = $company->getId();
                $companyDatabase = $this->companyDatabaseService->findByCompanyId($companyId);
                $databaseProxyData = [
                    'email' => DemoCompany::DEMO_COMPANY_AGENT_EMAIL,
                    'fk_company_id' => $companyId,
                    'fk_company_database_id' => $companyDatabase->id,
                ];
                $this->databaseProxyManagerService->runForCompany(
                    $company->getId(),
                    fn () => $this->databaseProxyService->create($databaseProxyData)
                );
            }

            $agent = Agent::factory()
                ->for($minigrid)
                ->for($agentCommission, 'commission')
                ->for($person)
                ->create([
                    'email' => $firstAgent ? DemoCompany::DEMO_COMPANY_AGENT_EMAIL : fake()->safeEmail(),
                    'password' => $firstAgent ? DemoCompany::DEMO_COMPANY_PASSWORD : fake()->password(),
                ]);
            $firstAgent = false; // Ensure only one agent gets the test email

            // Give our Agent some balance
            $agent_charge = AgentCharge::factory()
                ->for($agent)
                ->create();
        }
    }
}
