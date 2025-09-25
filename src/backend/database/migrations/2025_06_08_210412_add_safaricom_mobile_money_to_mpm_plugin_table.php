<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up() {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::SAFARICOM_MOBILE_MONEY,
                'name' => 'SafaricomMobileMoney',
                'description' => 'Safaricom M-PESA integration for MicroPowerManager.',
                'tail_tag' => 'Safaricom M-PESA',
                'installation_command' => 'safaricom-mobile-money:install',
                'root_class' => 'SafaricomMobileMoney',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function down() {
        DB::table('mpm_plugins')
            ->where('id', MpmPlugin::SAFARICOM_MOBILE_MONEY)
            ->delete();
    }
}; 