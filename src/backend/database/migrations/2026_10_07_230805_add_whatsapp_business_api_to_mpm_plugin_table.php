<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::WHATSAPP_BUSINESS_API,
                'name' => 'WhatsappBusinessApi',
                'description' => 'This plugin adds WhatsappBusinessApi functionality to MicroPowerManager.',
                'installation_command' => 'WhatsappBusinessApi:install',
                'root_class' => 'WhatsappBusinessApi',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function down(): void {
        DB::table('mpm_plugins')->where('id', MpmPlugin::WHATSAPP_BUSINESS_API)->delete();
    }
};
