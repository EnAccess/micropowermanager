<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::connection('tenant')->table('wave_money_transactions', static function (Blueprint $table) {
            $table->string('meter_serial')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('wave_money_transactions', function (Blueprint $table) {
            $table->integer('meter_serial')->nullable()->change();
        });
    }
};
