<?php

namespace Database\Seeders;

use App\Models\Address\Address;
use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\Device;
use App\Models\EBike;
use App\Models\GeographicalInformation;
use App\Models\Manufacturer;
use App\Models\Person\Person;
use Illuminate\Database\Seeder;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class EbikeSeeder extends Seeder {
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
        $demoManufacturer = Manufacturer::create([
            'name' => 'Demo E-bike Manufacturer',
            'type' => 'e-bike',
            'website' => 'https://demo.micropowermanager.io/',
            'contact_person' => 'Demo Ebike Person',
            'api_name' => 'DemoEbikeManufacturerApi',
        ]);

        $applianceType = ApplianceType::where('name', 'E-bike')->first();

        $ebikeAppliances = Appliance::factory()
            ->count(3)
            ->for($applianceType)
            ->sequence(
                ['name' => 'EcoMotion CityPro',   'price' => 180000],
                ['name' => 'TerraCharge TrailMaster', 'price' => 320000],
            )
            ->create();

        $persons = Person::all();

        $percentage = rand(20, 40);
        $numberOfCustomers = (int) ceil($persons->count() * ($percentage / 100));
        $selectedPersons = $persons->random($numberOfCustomers);

        foreach ($selectedPersons as $person) {
            $ebike = EBike::factory()
                ->for($ebikeAppliances->random(), 'appliance')
                ->for($demoManufacturer)
                ->create();

            Device::factory()
                ->for($person)
                ->for($ebike, 'device')
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
                    'device_serial' => $ebike->serial_number,
                ]);
        }

        for ($i = 1; $i <= 5; ++$i) {
            $ebike = EBike::factory()
                ->for($ebikeAppliances->random(), 'appliance')
                ->for($demoManufacturer)
                ->create();

            Device::factory()
                ->for($ebike, 'device')
                ->create([
                    'device_serial' => $ebike->serial_number,
                ]);
        }
    }
}
