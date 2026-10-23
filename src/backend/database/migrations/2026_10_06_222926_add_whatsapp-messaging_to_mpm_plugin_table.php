<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::WHATSAPP_MESSAGING,
                'name' => 'WhatsappMessaging',
                'description' => 'This plugin delivers MicroPowerManager notifications to customers over the WhatsApp Business API.',
                'installation_command' => 'whatsapp-messaging:install',
                'root_class' => 'WhatsappMessaging',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function down(): void {
        DB::table('mpm_plugins')->where('id', MpmPlugin::WHATSAPP_MESSAGING)->delete();
    }
};
