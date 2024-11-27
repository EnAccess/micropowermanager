<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('mpm_plugins', function (Blueprint $table) {
            $table->text('description')->change();
        });

        $mpm_plugins_mapping = [
            MpmPlugin::STEAMACO_METER => 'This plugin integrates Steamaco meters to Micropowermanager. It uses the same  credentials as ui.steama.co for authentication. After it got authenticated, the plugin synchronizes Site, Customer ..',
            MpmPlugin::BULK_REGISTRATION => 'This plugin provides bulk registration of the company\'s existing records. NOTE: Please do not use this plugin to register your Spark & Stemaco meter records. These records will be synchronized automatically once you configure your credential settings for these plugins.',
        ];

        foreach ($mpm_plugins_mapping as $id => $value) {
            DB::table('mpm_plugins')->where('id', $id)->update([
                'description' => $value,
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        $mpm_plugins_mapping = [
            MpmPlugin::STEAMACO_METER => 'This plugin integrates Steamaco meters to Micropowermanager. It uses the same  credentials as ui.steama.co for authentication. After it got authenticated, the plugin synchronizes Site, Customer ..',
            MpmPlugin::BULK_REGISTRATION => 'This plugin provides bulk registration of the company\'s existing records. NOTE: Please do not use this plugin to register your Spark & Stemaco meter records. These records will be synchronized automatically once you configure your credential settings for these plugins.',
        ];

        foreach ($mpm_plugins_mapping as $id => $value) {
            DB::table('mpm_plugins')->where('id', $id)->update([
                'description' => substr($value, 0, 191),
            ]);
        }

        Schema::table('mpm_plugins', function (Blueprint $table) {
            $table->string('description')->change();
        });
    }
};
