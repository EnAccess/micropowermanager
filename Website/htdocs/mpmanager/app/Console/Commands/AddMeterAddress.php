<?php

namespace App\Console\Commands;

use App\Models\Address\Address;
use App\Models\Meter\MeterParameter;
use Illuminate\Console\Command;

class AddMeterAddress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meters:addAddress';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates an address entry for all every registered meter. Sets them all to village 1';
    /**
     * @var MeterParameter
     */
    private $meterParameter;
    /**
     * @var Address
     */
    private $address;

    /**
     * Create a new command instance.
     *
     * @param MeterParameter $meterParameter
     * @param Address        $address
     */
    public function __construct(MeterParameter $meterParameter, Address $address)
    {
        parent::__construct();
        $this->meterParameter = $meterParameter;
        $this->address = $address;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
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

        return 0;
    }
}
