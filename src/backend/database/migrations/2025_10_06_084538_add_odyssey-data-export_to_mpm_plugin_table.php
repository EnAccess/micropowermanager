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
                'id' => MpmPlugin::ODYSSEY_DATA_EXPORT,
                'name' => 'OdysseyDataExport',
                'description' => 'This plugin developed for OdysseyDataExport functionality.',
                'tail_tag' => 'OdysseyDataExport',
                'installation_command' => 'odyssey-data-export:install',
                'root_class' => 'OdysseyDataExport',
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
            ->where('id', MpmPlugin::ODYSSEY_DATA_EXPORT)
            ->delete();
    }
};
