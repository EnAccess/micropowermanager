<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('micro_power_manager')->table('mpm_plugins', function (Blueprint $table) {
            $table->string('tail_tag')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mpm_plugins', function (Blueprint $table) {
            $table->dropColumn('tail_tag');
        });
    }
};
