<?php

namespace App\Console\Commands;

use App\Models\Address\Address;
use App\Models\Meter\MeterParameter;

class AddMeterAddress extends AbstractSharedCommand
{
    protected $signature = 'meters:addAddress';
    protected $description = 'Creates an address entry for all every registered meter. Sets them all to village 1';

    public function __construct(private MeterParameter $meterParameter, private Address $address)
    {
        parent::__construct();
    }

    public function runInCompanyScope(): void
    {
        $usedMeters = $this->meterParameter::all();

        foreach ($usedMeters as $meter) {
            $city = 1;
            if (($owner = $meter->owner()->first()) !== null) {
                $city = $owner->addresses()->first()->city_id;
            }
            $address = $this->address->create(
                [
                    'city_id' => $city,
                ]
            );
            $address->owner()->associate($meter);
            $address->geo()->associate($meter->geo()->first());
            $address->save();
        }
    }
}
