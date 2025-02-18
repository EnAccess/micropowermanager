<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up() {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::AFRICAS_TALKING,
                'name' => 'AfricasTalking',
                'description' => 'This plugin developed for the communication with customers throughout Africas Talking.',
                'tail_tag' => 'Africas Talking',
                'installation_command' => 'africas-talking:install',
                'root_class' => 'AfricasTalking',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function down() {
        DB::table('mpm_plugins')
            ->where('id', MpmPlugin::AFRICAS_TALKING)
            ->delete();
    }
};
