<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up() {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::DALY_BMS,
                'name' => 'DalyBms',
                'description' => 'This plugin developed for managing e-bikes with daly bms.',
                'tail_tag' => 'Daly Bms',
                'installation_command' => 'daly-bms:install',
                'root_class' => 'DalyBms',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function down() {
        DB::table('mpm_plugins')
            ->where('id', MpmPlugin::DALY_BMS)
            ->delete();
    }
};
