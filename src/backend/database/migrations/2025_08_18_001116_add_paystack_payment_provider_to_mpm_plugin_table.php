<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::PAYSTACK_PAYMENT_PROVIDER,
                'name' => 'PaystackPaymentProvider',
                'description' => 'This plugin developed to payment via paystack payment provider',
                'tail_tag' => 'Paystack Payment Provider',
                'installation_command' => 'paystack-payment-provider:install',
                'root_class' => 'PaystackPaymentProvider',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        DB::table('mpm_plugins')
            ->where('id', MpmPlugin::PAYSTACK_PAYMENT_PROVIDER)
            ->delete();
    }
};
