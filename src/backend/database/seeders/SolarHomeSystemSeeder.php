<?php

namespace Database\Seeders;

use App\Models\Address\Address;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Device;
use App\Models\GeographicalInformation;
use App\Models\Manufacturer;
use App\Models\Person\Person;
use App\Models\SolarHomeSystem;
use App\Services\DatabaseProxyManagerService;
use Illuminate\Database\Seeder;

class SolarHomeSystemSeeder extends Seeder {
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
        // Manufacturer
        // Create demo SHS manufacturer for demo purposes
        $demoShsManufacturer = Manufacturer::create([
            'name' => 'Demo SHS Manufacturer',
            'type' => 'shs',
            'website' => 'https://demo.micropowermanager.io/',
            'contact_person' => 'Demo Person',
            'api_name' => 'DemoShsManufacturerApi',
        ]);

        $manufacturers = collect([$demoShsManufacturer]);

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

        // Get available customers
        $persons = Person::all();

        // Calculate how many customers should get SHS (random between 20% and 40%)
        $percentage = rand(20, 40);
        $numberOfCustomers = (int) ceil($persons->count() * ($percentage / 100));

        // Get random subset of customers
        $selectedPersons = $persons->random($numberOfCustomers);

        // Create and assign SHS to selected customers
        foreach ($selectedPersons as $person) {
            // Create a Solar Home System
            $solarHomeSystem = SolarHomeSystem::factory()
                ->for(Asset::all()->random(), 'appliance')
                ->for($demoShsManufacturer)
                ->create();

            // Assign the SHS to the customer by creating a device
            $device = Device::factory()
                ->for($person)
                ->for($solarHomeSystem, 'device')
                ->has(
                    Address::factory()
                        ->for($person->addresses->first()->city)
                        ->has(
                            GeographicalInformation::factory()
                                // https://github.com/larastan/larastan/issues/2307
                                // @phpstan-ignore argument.type
                                ->state(function (array $attributes, Address $address) {
                                    /** @var Device $device */
                                    $device = $address->owner()->first();

                                    return ['points' => $device->person->addresses->first()->geo->points];
                                })
                                ->randomizePointsInHousehold(),
                            'geo'
                        )
                )
                ->create([
                    'device_serial' => $solarHomeSystem->serial_number,
                ]);
        }

        // Create additional not-yet-sold SHS
        for ($i = 1; $i <= 10; ++$i) {
            $solarHomeSystem = SolarHomeSystem::factory()
                ->for(Asset::all()->random(), 'appliance')
                ->for($demoShsManufacturer)
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
