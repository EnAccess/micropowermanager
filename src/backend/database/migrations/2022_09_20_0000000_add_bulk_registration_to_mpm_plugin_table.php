<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up() {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::BULK_REGISTRATION,
                'name' => 'BulkRegistration',
                'description' => substr('This plugin provides bulk registration of the company\'s existing records. NOTE: Please do not use this plugin to register your Spark & Stemaco meter records. These records will be synchronized automatically once you configure your credential settings for these plugins.', 0, 191),
                'tail_tag' => null,
                'installation_command' => 'bulk-registration:install',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function down() {
        DB::table('mpm_plugins')
            ->where('id', MpmPlugin::BULK_REGISTRATION)
            ->delete();
    }
};
