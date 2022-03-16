<?php


use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConnectionGroupSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('connection_groups')->insert([
            'name' => 'default connection group',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
