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
                'id' => MpmPlugin::SUN_KING_SHS,
                'name' => 'SunKingSHS',
                'description' => 'This plugin integrates SunKing solar home systems to Micropowermanager. It uses client_id & client_secret for creating tokens for energy.',
                'tail_tag' => 'SunKing SHS',
                'installation_command' => 'sun-king-shs:install',
                'root_class' => 'SunKingSHS',
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
            ->where('id', MpmPlugin::SUN_KING_SHS)
            ->delete();
    }
};
