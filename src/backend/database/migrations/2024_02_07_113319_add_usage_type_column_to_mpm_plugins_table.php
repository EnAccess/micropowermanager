<?php

use App\Models\MpmPlugin;
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
            $table->enum('usage_type', [
                'mini-grid',
                'shs',
                'e-bike',
                'general',
            ])->default('general')->after('id');
        });

        $mpm_plugins_mapping = [
            MpmPlugin::SPARK_METER => 'mini-grid',
            MpmPlugin::STEAMACO_METER => 'mini-grid',
            MpmPlugin::CALIN_METER => 'mini-grid',
            MpmPlugin::CALIN_SMART_METER => 'mini-grid',
            MpmPlugin::KELIN_METER => 'mini-grid',
            MpmPlugin::STRON_METER => 'mini-grid',
            MpmPlugin::SWIFTA_PAYMENT_PROVIDER => 'general',
            MpmPlugin::MESOMB_PAYMENT_PROVIDER => 'general',
            MpmPlugin::BULK_REGISTRATION => 'general',
            MpmPlugin::VIBER_MESSAGING => 'general',
            MpmPlugin::WAVE_MONEY_PAYMENT_PROVIDER => 'general',
            MpmPlugin::MICRO_STAR_METERS => 'mini-grid',
            MpmPlugin::SUN_KING_SHS => 'shs',
            MpmPlugin::GOME_LONG_METERS => 'mini-grid',
            MpmPlugin::WAVECOM_PAYMENT_PROVIDER => 'general',
            MpmPlugin::DALY_BMS => 'e-bike',
            MpmPlugin::AGAZA_SHS => 'shs',
        ];

        foreach ($mpm_plugins_mapping as $id => $value) {
            DB::table('mpm_plugins')->where('id', $id)->update([
                'usage_type' => $value,
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
            $table->dropColumn('usage_type');
        });
    }
};
