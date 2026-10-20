<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::WHATSAPP,
                'name' => 'WhatsApp',
                'description' => 'This plugin delivers transactional notifications (payment receipts, low-balance warnings, meter tokens) over the WhatsApp Business Cloud API.',
                'installation_command' => 'whatsapp:install',
                'root_class' => 'WhatsApp',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    /**
     * Reverse the migration.
     */
    public function down(): void {
        DB::table('mpm_plugins')
            ->where('id', MpmPlugin::WHATSAPP)
            ->delete();
    }
};
