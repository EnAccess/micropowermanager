<?php

namespace Database\Seeders;

use App\Models\ConnectionGroup;
use App\Models\ConnectionType;
use App\Models\SubConnectionType;
use Illuminate\Database\Seeder;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class ConnectionTypeGroupSeeder extends Seeder {
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
        // Delete defaults provided by migration
        ConnectionType::truncate();
        SubConnectionType::truncate();
        ConnectionGroup::truncate();

        // Connection Group / Connection Type
        $connectionTypes = ConnectionType::factory()
            ->forEachSequence(
                ['name' => 'Household'],
                ['name' => 'Institutional'],
                ['name' => 'Commercial'],
                ['name' => 'Productive Use / Industrial'],
                ['name' => 'Not Specified'],
            )
            ->create();

        foreach ($connectionTypes as $type) {
            // Skip subtypes for Household and Not Specified
            if (in_array($type->name, ['Household', 'Not Specified'])) {
                continue;
            }

            if ($type->name === 'Institutional') {
                SubConnectionType::factory()
                    ->for($type, 'connectionType')
                    ->forEachSequence(
                        ['name' => 'Primary school'],
                        ['name' => 'Secondary school'],
                        ['name' => 'Health clinic'],
                        ['name' => 'Rural health post'],
                        ['name' => 'Community center'],
                        ['name' => 'Religious facility'],
                        ['name' => 'Government office'],
                        ['name' => 'Police post'],
                        ['name' => 'Water pumping station'],
                        ['name' => 'Agricultural extension office'],
                    )
                    ->create();
            }

            if ($type->name === 'Commercial') {
                SubConnectionType::factory()
                    ->for($type, 'connectionType')
                    ->forEachSequence(
                        ['name' => 'Retail shop / kiosk'],
                        ['name' => 'Bar / restaurant / café'],
                        ['name' => 'Guesthouse / lodge'],
                        ['name' => 'Workshop (e.g., carpentry, metalwork)'],
                        ['name' => 'Tailoring / sewing business'],
                        ['name' => 'Barber shop / beauty salon'],
                        ['name' => 'Cold store / fish preservation unit'],
                        ['name' => 'Milling business (grain or cassava mill)'],
                        ['name' => 'Phone charging station'],
                        ['name' => 'Small manufacturing unit'],
                        ['name' => 'Agro-processing center'],
                        ['name' => 'Market stall'],
                    )
                    ->create();
            }

            if ($type->name === 'Productive Use / Industrial') {
                SubConnectionType::factory()
                    ->for($type, 'connectionType')
                    ->forEachSequence(
                        ['name' => 'Ice-making plant'],
                        ['name' => 'Welding workshop'],
                        ['name' => 'Water purification plant'],
                        ['name' => 'Brick making operation'],
                        ['name' => 'Irrigation pumping system'],
                    )
                    ->create();
            }
        }

        ConnectionGroup::factory()
            ->forEachSequence(
                ['name' => 'Pilot Mini-grid A (Lakeview)'],
                ['name' => 'Pilot Mini-grid B (Hilltop)'],
                ['name' => 'Institutional Electrification Program 2025'],
                ['name' => 'Commercial Productive Use Initiative'],
                ['name' => 'Solar Village Cluster North'],
                ['name' => 'Solar Village Cluster South'],
                ['name' => 'Donor-Funded Expansion Project'],
                ['name' => 'Private Concession Area 3'],
            )
            ->create();
    }
}
