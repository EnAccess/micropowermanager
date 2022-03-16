<?php


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SmsRecendInformationKeySeeder extends Seeder
{
    public function run()
    {
        DB::table('sms_resend_information_keys')->insert(['key' => 'Resend']);
    }
}