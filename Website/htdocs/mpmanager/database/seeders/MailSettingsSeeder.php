<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MailSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::connection('shard')->table('mail_settings')->insert([
            'mail_host' => 'smtp.example.com',
            'mail_port' => 123,
            'mail_encryption' => 'tls',
            'mail_username' => 'example@domain.com',
            'mail_password' => '123123',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
