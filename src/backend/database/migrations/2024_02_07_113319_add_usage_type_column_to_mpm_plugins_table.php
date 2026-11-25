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
            $table->enum('usage_type', [
                'mini-grid',
                'shs',
                'e-bike',
                'general',
            ])->default('general')->after('id');
        });

        $mpm_plugins_mapping = [
            1 => 'mini-grid',
            2 => 'mini-grid',
            3 => 'mini-grid',
            4 => 'mini-grid',
            5 => 'mini-grid',
            6 => 'mini-grid',
            7 => 'general',
            8 => 'general',
            9 => 'general',
            10 => 'general',
            11 => 'general',
            12 => 'mini-grid',
            13 => 'shs',
            14 => 'mini-grid',
            15 => 'general',
            16 => 'e-bike',
            17 => 'shs',
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
