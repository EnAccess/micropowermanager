<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::TEXTBEE_SMS_GATEWAY,
                'name' => 'TextbeeSmsGateway',
                'description' => 'This plugin developed to allow you handle your SMS needs using only your mobile device.',
                'tail_tag' => 'TextbeeSmsGateway',
                'installation_command' => 'textbee-sms-gateway:install',
                'root_class' => 'TextbeeSmsGateway',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function down(): void {
        DB::table('mpm_plugins')->where('id', MpmPlugin::TEXTBEE_SMS_GATEWAY)->delete();
    }
};
