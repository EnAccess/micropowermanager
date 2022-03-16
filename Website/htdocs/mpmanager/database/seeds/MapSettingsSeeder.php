<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MapSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('map_settings')->insert([
            'zoom' => 7,
            'latitude' => -2.500380,
            'longitude' => 32.889060,
            'provider' => 'Open Street Map',
            'bingMapApiKey' => '---',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
