<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up() {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::MICRO_STAR_METERS,
                'name' => 'MicroStarMeter',
                'description' => 'This plugin integrates MicroStar meters to Micropowermanager. It uses user_id & api_key for creating tokens for energy.',
                'tail_tag' => 'MicroStar Meter',
                'installation_command' => 'micro-star-meter:install',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function down() {
        DB::table('mpm_plugins')
            ->where('id', MpmPlugin::MICRO_STAR_METERS)
            ->delete();
    }
};
