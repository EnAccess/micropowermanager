<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up() {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::CHINT_METER,
                'usage_type' => 'mini-grid',
                'name' => 'ChintMeter',
                'description' => 'This plugin integrates Chint meters to Micropowermanager. It uses username & password for creating tokens for energy.',
                'tail_tag' => 'Chint Meter',
                'installation_command' => 'chint-meter:install',
                'root_class' => 'ChintMeter',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function down() {
        DB::table('mpm_plugins')
            ->where('id', MpmPlugin::CHINT_METER)
            ->delete();
    }
};
