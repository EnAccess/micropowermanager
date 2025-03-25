<?php

namespace App\Console\Commands;

use App\Models\Address\Address;
use App\Models\Meter\Meter;

class AddMeterAddress extends AbstractSharedCommand {
    protected $signature = 'meters:addAddress';
    protected $description = 'Creates an address entry for all every registered meter. Sets them all to village 1';

    public function __construct(private Meter $meter, private Address $address) {
        parent::__construct();
    }

    public function runInCompanyScope(): void {
        $usedMeters = $this->meter::all();

        foreach ($usedMeters as $meter) {
            $city = 1;
            if (($device = $meter->device()->first()) !== null) {
                $city = $device->address()->first()->city_id;
            }
            $address = $this->address->create(
                [
                    'city_id' => $city,
                ]
            );
            $address->owner()->associate($meter);
            if ($meter->device->person->addresses()->first()->geo()->exists()) {
                $address->geo()->create($meter->device->person->addresses()->first()->geo()->first()->toArray());
            }
            $address->save();
        }
    }
}
