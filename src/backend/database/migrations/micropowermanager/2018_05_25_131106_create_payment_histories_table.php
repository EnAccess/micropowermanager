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
        Schema::connection('tenant')->create('payment_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('amount');
            $table->integer('transaction_id');
            $table->string('payment_service');
            $table->string('sender');
            $table->string('payment_type'); // energy, loand, etc.
            $table->morphs('paid_for'); // meter_id, loan_id
            $table->morphs('payer');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('payment_histories');
    }
};
