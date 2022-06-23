<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TicketSettingsSeeder extends Seeder
{
    public function run()
    {
        DB::connection('shard')->table('ticket_settings')->insert([
            'name' => 'Trello',
            'api_token' => '----',
            'api_url' => 'https://api.trello.com/1',
            'api_key' => '----',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
