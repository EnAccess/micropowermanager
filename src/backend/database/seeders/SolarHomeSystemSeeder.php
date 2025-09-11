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
use Illuminate\Database\Seeder;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

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
        // Create dummy SHS manufacturer for demo purposes
        $sunKingManufacturer = Manufacturer::create([
            'name' => 'Dummy SunKing SHS',
            'type' => 'shs',
            'website' => 'https://example.com/sunking',
            'contact_person' => 'Demo Person',
            'api_name' => 'DummySunKingSHSApi',
        ]);

        $manufacturers = collect([$sunKingManufacturer]);

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
                ->for($sunKingManufacturer)
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
                                // Remove this after Laravel 12 upgrade, see
                                // https://github.com/larastan/larastan/issues/2307
                                // @phpstan-ignore-next-line
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
                ->for($sunKingManufacturer)
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
