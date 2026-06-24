<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::SAFARICOM_KE_PAYMENT_PROVIDER,
                'name' => 'SafaricomKePaymentProvider',
                'description' => 'Safaricom M-PESA integration for MicroPowerManager.',
                'tail_tag' => 'Safaricom M-PESA',
                'installation_command' => 'safaricom-ke-payment-provider:install',
                'root_class' => 'SafaricomKePaymentProvider',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function down(): void {
        DB::table('mpm_plugins')
            ->where('id', MpmPlugin::SAFARICOM_KE_PAYMENT_PROVIDER)
            ->delete();
    }
};
