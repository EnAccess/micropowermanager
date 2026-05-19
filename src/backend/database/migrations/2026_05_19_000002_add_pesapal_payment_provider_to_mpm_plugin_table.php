<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::PESAPAL_PAYMENT_PROVIDER,
                'name' => 'PesapalPaymentProvider',
                'description' => 'Accept online payments from customers via PesaPal hosted checkout',
                'tail_tag' => 'Pesapal Payment Provider',
                'installation_command' => 'pesapal-payment-provider:install',
                'root_class' => 'PesapalPaymentProvider',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function down(): void {
        DB::table('mpm_plugins')
            ->where('id', MpmPlugin::PESAPAL_PAYMENT_PROVIDER)
            ->delete();
    }
};
