<?php

namespace Database\Seeders;

use App\Models\AccessRate\AccessRate;
use App\Models\Address\Address;
use App\Models\ConnectionGroup;
use App\Models\ConnectionType;
use App\Models\Device;
use App\Models\GeographicalInformation;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterTariff;
use App\Models\Meter\MeterType;
use App\Models\Person\Person;
use Illuminate\Database\Seeder;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class MeterSeeder extends Seeder {
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
        // Here, we adding some fake Manufacturers for seeding.
        // Additional (actual) manufacturers can be added by
        // enabling to corresponding plugin in the demo environment.
        $manufacturers = Manufacturer::factory()
            ->count(3)
            ->isMeterManufacturer()
            ->create();

        // Connection Group / Connection Type
        ConnectionType::create(['name' => 'House Hold']);
        ConnectionType::create(['name' => 'Commercial Usage']);
        ConnectionType::create(['name' => 'Productive Usage']);
        ConnectionType::create(['name' => 'Residential']);
        ConnectionType::create(['name' => 'Business']);
        ConnectionType::create(['name' => 'Institution']);
        ConnectionType::create(['name' => 'Not Specified']);

        ConnectionGroup::create(['name' => 'Household']);
        ConnectionGroup::create(['name' => 'Community']);
        ConnectionGroup::create(['name' => 'Cafeterias and Pubs']);
        ConnectionGroup::create(['name' => 'Hair Dressers']);
        ConnectionGroup::create(['name' => 'Churches & Mosques']);
        ConnectionGroup::create(['name' => 'Solar Excess']);
        ConnectionGroup::create(['name' => 'H.H Cus']);
        ConnectionGroup::create(['name' => 'FOOD AND BEVERAGES']);
        ConnectionGroup::create(['name' => 'SHOPS w']);
        ConnectionGroup::create(['name' => 'SCHOOLS']);
        ConnectionGroup::create(['name' => 'HEALTH CENTRES & INSTITUTIONS']);
        ConnectionGroup::create(['name' => 'GUEST HOUSES']);
        ConnectionGroup::create(['name' => 'ENTERTAINMENT CENTRES']);
        ConnectionGroup::create(['name' => 'Mobile Charging, OFFICES and Stationery']);
        ConnectionGroup::create(['name' => 'Small scale HATCHERIES']);
        ConnectionGroup::create(['name' => 'Laundry']);
        ConnectionGroup::create(['name' => 'Freezing Units']);
        ConnectionGroup::create(['name' => 'Wood work']);
        ConnectionGroup::create(['name' => 'Mills']);
        ConnectionGroup::create(['name' => 'Welders']);
        ConnectionGroup::create(['name' => 'Drinking Water Project']);
        ConnectionGroup::create(['name' => 'Bakery']);
        ConnectionGroup::create(['name' => 'Not ordered yet']);

        // Tariffs
        MeterTariff::factory()
            ->create([
                'name' => 'Simple Tariff',
                'price' => '250',
                'total_price' => '250',
                'currency' => 'TZS',
            ]);

        MeterTariff::factory()
            ->has(
                AccessRate::factory()
                    ->state([
                        'amount' => '1000',
                        'period' => '30',
                    ])
            )
            ->create([
                'name' => 'Tariff with monthly Access Rate',
                'price' => '150',
                'total_price' => '150',
                'currency' => 'TZS',
            ]);

        // TODO: Tariff with Additional Pricing Components
        // TODO: Tariff with Time of Usage

        // Meter Types
        $clusters = MeterType::factory()
            ->count(3)
            ->sequence(
                [
                    'online' => 0,
                    'phase' => '1',
                    'max_current' => '5',
                ],
                [
                    'online' => 1,
                    'phase' => '1',
                    'max_current' => '60',
                ],
                [
                    'online' => 1,
                    'phase' => '2',
                    'max_current' => '60',
                ],
            )
            ->create();

        // Actual Meters

        // Get available customers
        $persons = Person::all();

        // Assign one meter to each customer
        foreach ($persons as $person) {
            // Create a Meter
            $meter = Meter::factory()
                ->for(ConnectionType::all()->random())
                ->for(ConnectionGroup::all()->random())
                ->for(MeterType::all()->random())
                ->for(Manufacturer::all()->random())
                ->for(MeterTariff::all()->random(), 'tariff')
                ->create();

            // Assign the Meter to the customer by creating a device
            $device = Device::factory()
                ->for($person)
                ->for($meter, 'device')
                ->has(
                    Address::factory()
                        ->for($person->addresses->first()->city)
                        ->has(
                            GeographicalInformation::factory()
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
                    'device_serial' => $meter->serial_number,
                ]);
        }
    }
}
