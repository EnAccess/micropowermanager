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
                'id' => MpmPlugin::DEMO_METER_MANUFACTURER,
                'name' => 'DemoMeterManufacturer',
                'description' => 'Demo manufacturer plugin for meters that generates realistic tokens and transactions without requiring real manufacturer API integration. Perfect for testing and demonstration purposes.',
                'tail_tag' => 'DemoMeterManufacturer',
                'installation_command' => 'demo-meter-manufacturer:install',
                'root_class' => 'DemoMeterManufacturer',
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
            ->where('id', MpmPlugin::DEMO_METER_MANUFACTURER)
            ->delete();
    }
};
