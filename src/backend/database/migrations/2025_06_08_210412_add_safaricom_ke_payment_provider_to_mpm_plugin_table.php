<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::table('mpm_plugins')->insert([
            [
                'id' => 31,
                'name' => 'SafaricomKePaymentProvider',
                'description' => 'Safaricom M-PESA integration for MicroPowerManager.',
                'installation_command' => 'safaricom-ke-payment-provider:install',
                'root_class' => 'SafaricomKePaymentProvider',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function down(): void {
        DB::table('mpm_plugins')
            ->where('id', 31)
            ->delete();
    }
};
