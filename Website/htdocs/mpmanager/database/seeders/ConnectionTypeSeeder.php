<?php

namespace Database\Seeders;

use App\Models\ConnectionType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConnectionTypeSeeder extends Seeder
{

    public function run(): void
    {
        DB::connection('shard')->table('connection_types')->insert([
            'name' => 'default connection type',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

    }
}
