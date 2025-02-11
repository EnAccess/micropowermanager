<?php

use Carbon\Carbon;
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
        Schema::connection('tenant')->table('p_v_s', function (Blueprint $table) {
            $table->double('max_theoretical_output')->default(0);
            $table->dateTime('reading_date')->default(Carbon::now()->format('Y-m-d H:i:s'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('p_v_s', function (Blueprint $table) {});
    }
};
