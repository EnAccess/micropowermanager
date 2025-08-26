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
                'id' => MpmPlugin::WAVECOM_PAYMENT_PROVIDER,
                'name' => 'WavecomPayment',
                'description' => 'This plugin developed for getting Wavecom(Senegal) payments into MicroPowerManager.',
                'tail_tag' => null,
                'installation_command' => 'wavecom-payment-provider:install',
                'root_class' => 'WavecomPaymentProvider',
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
            ->where('id', MpmPlugin::WAVECOM_PAYMENT_PROVIDER)
            ->delete();
    }
};
