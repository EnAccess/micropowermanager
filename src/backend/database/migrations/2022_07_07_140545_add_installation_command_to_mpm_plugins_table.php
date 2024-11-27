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
            $table->string('installation_command')->nullable();
        });

        $mpm_plugins_mapping = [
            MpmPlugin::SPARK_METER => 'spark-meter:install',
            MpmPlugin::STEAMACO_METER => 'steama-meter:install',
            MpmPlugin::CALIN_METER => 'calin-meter:install',
            MpmPlugin::CALIN_SMART_METER => 'calin-smart-meter:install',
            MpmPlugin::KELIN_METER => 'kelin-meter:install',
            MpmPlugin::STRON_METER => 'stron-meter:installl',
            MpmPlugin::SWIFTA_PAYMENT_PROVIDER => 'swifta-payment-provider:install',
            MpmPlugin::MESOMB_PAYMENT_PROVIDER => 'mesomb-payment-provider:install',
        ];

        foreach ($mpm_plugins_mapping as $id => $value) {
            DB::table('mpm_plugins')->where('id', $id)->update([
                'installation_command' => $value,
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
            $table->dropColumn('installation_command');
        });
    }
};
