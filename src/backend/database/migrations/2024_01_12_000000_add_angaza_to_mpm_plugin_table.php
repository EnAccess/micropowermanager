<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up() {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::AGAZA_SHS,
                'name' => 'AngazaSHS',
                'description' => 'This plugin integrates Angaza solar home systems to Micropowermanager. It uses client_id & client_secret for creating tokens for energy.',
                'tail_tag' => 'Angaza SHS',
                'installation_command' => 'angaza-shs:install',
                'root_class' => 'AngazaSHS',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function down() {
        DB::table('mpm_plugins')
            ->where('id', MpmPlugin::AGAZA_SHS)
            ->delete();
    }
};
