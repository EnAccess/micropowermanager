<?php

namespace Database\Seeders;

use App\Models\Address\Address;
use App\Models\GeographicalInformation;
use App\Models\MaintenanceUsers;
use App\Models\MiniGrid;
use App\Models\Person\Person;
use Illuminate\Database\Seeder;
use Inensus\Ticket\Models\TicketCategory;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class TicketSeeder extends Seeder {
    public function __construct(
        private DatabaseProxyManagerService $databaseProxyManagerService,
    ) {
        $this->databaseProxyManagerService->buildDatabaseConnectionDummyCompany();
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        // Create Ticket categories
        TicketCategory::newFactory()
            ->count(2)
            ->sequence(
                [
                    'label_name' => 'Internal',
                    'label_color' => 'lime',
                    'out_source' => 0,
                ],
                [
                    'label_name' => 'Outsource',
                    'label_color' => 'sky',
                    'out_source' => 1,
                ]
            )
            ->create();

        // Create Maintenance Users

        // Get available MiniGrids
        $minigrids = MiniGrid::all();

        // For each Mini-Grid we create one Maintenance User
        foreach ($minigrids as $minigrid) {
            $village = $minigrid->cities()->get()->random();

            $person = Person::factory()
                ->isMaintenanceUser($village->name)
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

            // Make the person a Maintenance User
            $maintenanceUser = MaintenanceUsers::factory()
                ->for($minigrid)
                ->for($person)
                ->create();

            // Find the MiniGrid's Agent and also make them a Maintenance User
            $agentPerson = $minigrid->agent()->first()->person()->first();

            $maintenanceUserAgent = MaintenanceUsers::factory()
                ->for($minigrid)
                ->for($agentPerson)
                ->create();

            // dd($maintenanceUserAgent);
        }
    }
}
