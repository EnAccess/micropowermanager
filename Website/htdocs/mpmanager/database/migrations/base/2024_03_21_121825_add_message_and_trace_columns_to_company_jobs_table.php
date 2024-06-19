<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class  extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('micro_power_manager')->table('company_jobs', function (Blueprint $table) {
            $table->string('message')->nullable();
            $table->string('trace')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('micro_power_manager')->table('company_jobs', function (Blueprint $table) {
            //
        });
    }
};
