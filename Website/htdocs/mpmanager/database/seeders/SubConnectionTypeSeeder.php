<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubConnectionTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('shard')->table('sub_connection_types')->insert([
            'name' => 'default  sub connection type',
            'tariff_id' => 1,
            'connection_type_id' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
