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
            $table->string('tail_tag')->nullable();
        });

        $mpm_plugins_mapping = [
            MpmPlugin::SPARK_METER => 'Spark Meter',
            MpmPlugin::STEAMACO_METER => 'Steamaco Meter',
            MpmPlugin::CALIN_METER => 'Calin Meter',
            MpmPlugin::CALIN_SMART_METER => 'CalinSmart Meter',
            MpmPlugin::KELIN_METER => 'Kelin Meter',
            MpmPlugin::STRON_METER => 'Stron Meter',
            MpmPlugin::SWIFTA_PAYMENT_PROVIDER => null,
            MpmPlugin::MESOMB_PAYMENT_PROVIDER => null,
        ];

        foreach ($mpm_plugins_mapping as $id => $value) {
            DB::table('mpm_plugins')->where('id', $id)->update([
                'tail_tag' => $value,
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
            $table->dropColumn('tail_tag');
        });
    }
};
