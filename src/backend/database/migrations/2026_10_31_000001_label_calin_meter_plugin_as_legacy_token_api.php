<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::connection('micro_power_manager')
            ->table('mpm_plugins')
            ->where('id', MpmPlugin::CALIN_METER)
            ->update([
                'display_name' => 'Calin (Legacy Token API)',
                'description' => 'This plugin integrates Calin meters to Micropowermanager. It uses user_id & api_key for creating tokens for energy.',
                'updated_at' => Carbon::now(),
            ]);
    }

    public function down(): void {
        DB::connection('micro_power_manager')
            ->table('mpm_plugins')
            ->where('id', MpmPlugin::CALIN_METER)
            ->update([
                'display_name' => null,
                'description' => 'This plugin integrates Calin meters to Micropowermanager. It uses user_id & api_key for creating tokens for energy.',
                'updated_at' => Carbon::now(),
            ]);
    }
};
