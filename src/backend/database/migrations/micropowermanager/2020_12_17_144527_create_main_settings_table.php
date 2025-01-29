<?php

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
        Schema::connection('tenant')->create('main_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->char('site_title', 100);
            $table->char('company_name', 100);
            $table->char('currency', 10);
            $table->char('country', 100);
            $table->char('language', 10);
            $table->float('vat_energy', 5);
            $table->float('vat_appliance', 5);
            $table->timestamps();
        });

        DB::connection('tenant')->table('main_settings')->insert([
            'site_title' => 'MPM - The easiest way to manage your Mini-Grid',
            'company_name' => 'MicroPowerManager',
            'currency' => '€',
            'country' => 'Germany',
            'vat_energy' => 1,
            'vat_appliance' => 18,
            'language' => 'en',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('main_settings');
    }
};
