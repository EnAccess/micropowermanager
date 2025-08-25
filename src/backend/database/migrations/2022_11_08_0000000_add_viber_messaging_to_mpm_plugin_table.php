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
                'id' => MpmPlugin::VIBER_MESSAGING,
                'name' => 'ViberMessaging',
                'description' => 'This plugin developed for the communication with customers throughout Viber messages.',
                'tail_tag' => 'Viber Messaging',
                'installation_command' => 'viber-messaging:install',
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
            ->where('id', MpmPlugin::VIBER_MESSAGING)
            ->delete();
    }
};
