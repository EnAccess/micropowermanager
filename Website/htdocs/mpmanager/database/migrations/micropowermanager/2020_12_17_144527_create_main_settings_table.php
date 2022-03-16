<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('main_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->char('site_title',100);
            $table->char('company_name',100);
            $table->char('currency',10);
            $table->char('country',100);
            $table->char('language',10);
            $table->float('vat_energy',5);
            $table->float('vat_appliance',5);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('main_settings');
    }
};
