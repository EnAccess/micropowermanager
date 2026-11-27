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
            $table->string('tail_tag')->nullable();
        });

        $mpm_plugins_mapping = [
            1 => 'Spark Meter',
            2 => 'Steamaco Meter',
            3 => 'Calin Meter',
            4 => 'CalinSmart Meter',
            5 => 'Kelin Meter',
            6 => 'Stron Meter',
            7 => null,
            8 => null,
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
