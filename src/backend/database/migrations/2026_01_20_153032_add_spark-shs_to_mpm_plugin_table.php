<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::SPARK_SHS,
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
        DB::table('mpm_plugins')->where('id', MpmPlugin::SPARK_SHS)->delete();
    }
};
