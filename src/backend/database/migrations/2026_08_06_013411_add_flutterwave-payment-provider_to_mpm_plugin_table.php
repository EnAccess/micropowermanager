<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::FLUTTERWAVE_PAYMENT_PROVIDER,
                'name' => 'FlutterwavePaymentProvider',
                'description' => 'This plugin adds FlutterwavePaymentProvider functionality to MicroPowerManager.',
                'tail_tag' => 'FlutterwavePaymentProvider',
                'installation_command' => 'flutterwave-payment-provider:install',
                'root_class' => 'FlutterwavePaymentProvider',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function down(): void {
        DB::table('mpm_plugins')->where('id', MpmPlugin::FLUTTERWAVE_PAYMENT_PROVIDER)->delete();
    }
};
