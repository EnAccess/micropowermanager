<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::table('mpm_plugins')->insert([
            [
                'id' => 29,
                'name' => 'SmsTransactionParser',
                'description' => 'Parse incoming SMS messages from mobile money providers to create payment transactions',
                'tail_tag' => 'SmsTransactionParser',
                'installation_command' => 'sms-transaction-parser:install',
                'root_class' => 'SmsTransactionParser',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function down(): void {
        DB::table('mpm_plugins')->where('id', 29)->delete();
    }
};
