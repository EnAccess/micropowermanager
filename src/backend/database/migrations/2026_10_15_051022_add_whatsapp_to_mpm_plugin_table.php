<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::WHATSAPP,
                'name' => 'Whatsapp',
                'description' => 'This plugin adds Whatsapp functionality to MicroPowerManager.',
                'installation_command' => 'whatsapp:install',
                'root_class' => 'Whatsapp',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function down(): void {
        DB::table('mpm_plugins')->where('id', MpmPlugin::WHATSAPP)->delete();
    }
};
