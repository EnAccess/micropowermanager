<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::CALIN_METER_V2,
                'usage_type' => 'mini-grid',
                'name' => 'CalinMeterV2',
                'description' => 'Use this if Calin set up your own AMR system with its own address, often for LoRaWAN or gateway-connected meters, and gave you a user ID and password. Vends kWh tokens and clears credit. If you log in at ami.calinhost.com with a company name and vending password, use Calin Smart Meter.',
                'installation_command' => 'calin-meter-v2:install',
                'root_class' => 'CalinMeterV2',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function down(): void {
        DB::table('mpm_plugins')->where('id', MpmPlugin::CALIN_METER_V2)->delete();
    }
};
