<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('mpm_plugins', function (Blueprint $table) {
            $table->string('root_class')->nullable();
        });

        $mpm_plugins_mapping = [
            1 => 'SparkMeter',
            2 => 'SteamaMeter',
            3 => 'CalinMeter',
            4 => 'CalinSmartMeter',
            5 => 'KelinMeter',
            6 => 'StronMeter',
            7 => 'SwiftaPaymentProvider',
            8 => 'MesombPaymentProvider',
            9 => 'BulkRegistration',
            10 => 'ViberMessaging',
            11 => 'WaveMoneyPaymentProvider',
            12 => 'MicroStarMeter',
        ];

        foreach ($mpm_plugins_mapping as $id => $value) {
            DB::table('mpm_plugins')->where('id', $id)->update([
                'root_class' => $value,
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('mpm_plugins', function (Blueprint $table) {
            $table->dropColumn('root_class');
        });
    }
};
