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
                'id' => MpmPlugin::ECREEE_E_TENDER,
                'name' => 'Ecreee E-Tender',
                'description' => 'This plugin integrates Ecreee e-tender integration to Micropowermanager.',
                'tail_tag' => null,
                'installation_command' => 'ecreee-e-tender:install',
                'root_class' => 'EcreeeETender',
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
            ->where('id', MpmPlugin::ECREEE_E_TENDER)
            ->delete();
    }
};
