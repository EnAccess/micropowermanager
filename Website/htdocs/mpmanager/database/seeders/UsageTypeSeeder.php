<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsageTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::connection('micro_power_manager')->table('usage_types')->insert(array(
                [
                    'name' => 'Mini-Grid',
                    'value' => 'mini-grid',
                ],
                [
                    'name' => 'Solar Home System',
                    'value' => 'shs',
                ],
                [
                    'name' => 'EBike Rental',
                    'value' => 'e-bike',
                ],
                [
                    'name' => 'Mini-Grid & Solar Home System',
                    'value' => 'mini-grid&shs',
                ],
                [
                    'name' => 'Mini-Grid & EBike Rental',
                    'value' => 'mini-grid&e-bike',
                ],
                [
                    'name' => 'Solar Home & EBike Rental',
                    'value' => 'shs&e-bike',
                ],
                [
                    'name' => 'Mini-Grid & Solar Home System & EBike Rental',
                    'value' => 'mini-grid&shs&e-bike',
                ]
            )
        );
    }
}
