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
            $table->string('installation_command')->nullable();
        });

        $mpm_plugins_mapping = [
            1 => 'spark-meter:install',
            2 => 'steama-meter:install',
            3 => 'calin-meter:install',
            4 => 'calin-smart-meter:install',
            5 => 'kelin-meter:install',
            6 => 'stron-meter:install',
            7 => 'swifta-payment-provider:install',
            8 => 'mesomb-payment-provider:install',
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
