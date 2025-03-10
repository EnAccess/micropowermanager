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
        Schema::connection('tenant')->dropIfExists('upgrades');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->create('upgrades', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('restriction_id');
            $table->integer('cost');
            $table->integer('amount');
            $table->integer('period_in_months');
            $table->timestamps();
        });
    }
};
