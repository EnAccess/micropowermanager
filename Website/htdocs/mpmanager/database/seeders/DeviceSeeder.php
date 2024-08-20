<?php

namespace Database\Seeders;

use App\Models\Address\Address;
use App\Models\City;
use App\Models\GeographicalInformation;
use App\Models\Meter\Meter;
use App\Models\Person\Person;
use Illuminate\Database\Seeder;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

const DUMMY_COMPANY_ID = 1;

class DeviceSeeder extends Seeder
{
    public function __construct(
        private DatabaseProxyManagerService $databaseProxyManagerService,
    ) {
        $this->databaseProxyManagerService->buildDatabaseConnectionByCompanyId(DUMMY_COMPANY_ID);
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Manufacturer
        // For now, we just adding some dummy Manufacturers.
        // Later, this should probably be synced with the manufacturers
        // for which we have plugins in the Demo setup.

        // Connection Group / Connection Type

        // Tariff

        // Actual Meters

        // Get available customers
        $persons = Person::all();

        $meters = Meter::factory()
            ->count(1)
            ->make();

        dd($meters);
    }
}
