<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Device;
use App\Models\Manufacturer;
use App\Models\SolarHomeSystem;
use App\Services\CompanyService;
use Illuminate\Database\Seeder;

class SolarHomeSystemSeeder extends Seeder {
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
        // Manufacturer
        // Here, we adding some fake Manufacturers for seeding.
        // Additional (actual) manufacturers can be added by
        // enabling to corresponding plugin in the demo environment.
        $manufacturers = Manufacturer::factory()
            ->count(2)
            ->isShsManufacturer()
            ->create();

        // Get the SHS asset type
        $assetType = AssetType::where('name', 'Solar Home System')->first();

        // Create our appliances, i.e. sales deals (?)
        $appliances = Asset::factory()
            ->count(5)
            ->for($assetType)
            ->sequence(
                // Thank you ChatGPT for generating these names... 🤖
                [
                    'name' => 'SunPower Home 3000',
                    'price' => 500000,
                ],
                [
                    'name' => 'SunPower Home 1000',
                    'price' => 100000,
                ],
                ['name' => 'EcoBright Solar Kit'],
                ['name' => 'HelioVolt Pro Series'],
                ['name' => 'SolaraMax Home Energy'],
            )
            ->create();

        // Create not-yet-sold SHS such that we can "sell" them in the Demo
        for ($i = 1; $i <= 10; ++$i) {
            $solarHomeSystem = SolarHomeSystem::factory()
                ->for(Asset::all()->random(), 'appliance')
                ->for(Manufacturer::where('type', 'shs')->get()->random())
                ->create();

            $device = Device::factory()
                ->for($solarHomeSystem, 'device')
                ->create([
                    'device_serial' => $solarHomeSystem->serial_number,
                ]);
        }

        // Create already-sold SHS
        $sold_solar_home_systems = null;

        // TBD: assign appliances to Agents
        // This is required to use the Agent app.
    }
}
