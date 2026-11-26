<?php

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
                'id' => 32,
                'name' => 'FlutterwavePaymentProvider',
                'description' => 'This plugin developed to payment via flutterwave payment provider',
                'installation_command' => 'flutterwave-payment-provider:install',
                'root_class' => 'FlutterwavePaymentProvider',
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
            ->where('id', 32)
            ->delete();
    }
};
