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
        Schema::connection('tenant')->create('asset_rates', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('asset_person_id');
            $table->integer('rate_cost');
            $table->integer('remaining');
            $table->dateTime('due_date');
            $table->integer('remind');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('asset_rates');
    }
};
