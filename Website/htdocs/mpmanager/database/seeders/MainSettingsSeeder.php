<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MainSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('main_settings')->insert([
            'site_title' => 'MPM - The easiest way to manage your Mini-Grid',
            'company_name' => 'MicroPowerManager',
            'currency' => '€',
            'country' => 'Germany',
            'vat_energy' => 1,
            'vat_appliance' => 18,
            'language' => 'en',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

        ]);
    }
}
