<?php

namespace Database\Seeders;

use App\Models\Manufacturer;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ManufacturerSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('shard')->table('manufacturers')->insert([
            'name' => 'Calin Meters STS',
            'website' => 'http://www.calinmeter.com/',
            'api_name' => 'CalinApi',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

    }
}
