<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SmsResendInformationKeySeeder extends Seeder
{
    public function run()
    {
        DB::connection('shard')->table('sms_resend_information_keys')->insert(['key' => 'Resend']);
    }
}
