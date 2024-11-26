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
        Schema::create('mpm_plugins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->timestamps();
        });

        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::SPARK_METER,
                'name' => 'SparkMeter',
                'description' => 'This plugin uses KoiosAPI for the main authentication. After it got authenticated it uses the ThunderCloud API for basic CRUD operations. You need to enter the ThunderCloud Token on the site',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => MpmPlugin::STEAMACO_METER,
                'name' => 'SteamaMeter',
                'description' => substr('This plugin integrates Steamaco meters to Micropowermanager. It uses the same  credentials as ui.steama.co for authentication. After it got authenticated, the plugin synchronizes Site, Customer ..', 0, 191),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => MpmPlugin::CALIN_METER,
                'name' => 'CalinMeter',
                'description' => 'This plugin integrates Calin meters to Micropowermanager. It uses user_id & api_key for creating tokens for energy.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => MpmPlugin::CALIN_SMART_METER,
                'name' => 'CalinSmartMeter',
                'description' => 'This plugin integrates Calin meters to Micropowermanager. It uses company_name, user_name, password and password_vend for creating tokens for energy.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => MpmPlugin::KELIN_METER,
                'name' => 'KelinMeter',
                'description' => 'This plugin integrates Kelim meters to Micropowermanager. It uses username & password for creating tokens for energy.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => MpmPlugin::STRON_METER,
                'name' => 'StronMeter',
                'description' => 'This plugin integrates Stron meters to Micropowermanager. It uses the api login credentials for authentication.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => MpmPlugin::SWIFTA_PAYMENT_PROVIDER,
                'name' => 'SwiftaPayment',
                'description' => 'This plugin developed for getting Swifta payments into MicroPowerManager.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => MpmPlugin::MESOMB_PAYMENT_PROVIDER,
                'name' => 'MesombPayment',
                'description' => 'This plugin developed for getting MeSomb payments into MicroPowerManager.',
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
        Schema::dropIfExists('mpm_plugins');
    }
};
