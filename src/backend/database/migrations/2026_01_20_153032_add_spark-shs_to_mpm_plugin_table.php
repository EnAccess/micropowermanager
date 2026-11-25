<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::table('mpm_plugins')->insert([
            [
                'id' => 28,
                'name' => 'SparkShs',
                'description' => 'This plugin adds SparkShs functionality to MicroPowerManager.',
                'tail_tag' => 'SparkShs',
                'installation_command' => 'spark-shs:install',
                'root_class' => 'SparkShs',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function down(): void {
        DB::table('mpm_plugins')->where('id', 28)->delete();
    }
};
