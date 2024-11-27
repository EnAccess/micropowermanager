<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up() {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::WAVE_MONEY_PAYMENT_PROVIDER,
                'name' => 'WaveMoneyPayment',
                'description' => 'This plugin developed for getting WaveMoney payments into MicroPowerManager.',
                'tail_tag' => 'WaveMoney',
                'installation_command' => 'wave-money-payment-provider:install',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function down() {
        DB::table('mpm_plugins')
            ->where('id', MpmPlugin::WAVE_MONEY_PAYMENT_PROVIDER)
            ->delete();
    }
};
