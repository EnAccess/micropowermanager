<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::GOME_LONG_METERS,
                'name' => 'GomeLongMeter',
                'description' => 'This plugin integrates GomeLong meters to Micropowermanager. It uses. user_id & user_password for creating tokens for energy.',
                'tail_tag' => 'GomeLong Meter',
                'installation_command' => 'gome-long-meter:install',
                'root_class' => 'GomeLongMeter',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        DB::table('mpm_plugins')
            ->where('id', MpmPlugin::GOME_LONG_METERS)
            ->delete();
    }
};
