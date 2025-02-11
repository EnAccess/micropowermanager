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
        Schema::connection('tenant')->create('access_rate_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('meter_id');
            $table->integer('access_rate_id');
            $table->dateTime('due_date');
            $table->integer('debt')->default(0);
            $table->integer('unpaid_in_row')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('access_rate_payments');
    }
};
