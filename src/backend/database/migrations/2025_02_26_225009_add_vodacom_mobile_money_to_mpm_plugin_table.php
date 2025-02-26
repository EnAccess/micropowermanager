<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up() {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::VODACOM_MOBILE_MONEY,
                'name' => 'VodacomMobileMoney',
                'description' => 'This plugin developed to payment via vodacom mobile money provider',
                'tail_tag' => 'Vodacom Mobile Money',
                'installation_command' => 'vodacom-mobile-money:install',
                'root_class' => 'VodacomMobileMoney',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function down() {
        DB::table('mpm_plugins')
            ->where('id', MpmPlugin::VODACOM_MOBILE_MONEY)
            ->delete();
    }
};
