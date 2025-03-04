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
        Schema::connection('tenant')->dropIfExists('vodacom_transactions');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->create('vodacom_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('conversation_id')->unique();
            $table->string('originator_conversation_id')->unique();
            $table->string('mpesa_receipt');
            $table->dateTime('transaction_date');
            $table->string('transaction_id')->unique();
            $table->integer('status')->default(0);
            $table->timestamps();
        });

        Schema::connection('tenant')->table('vodacom_transactions', function (Blueprint $table) {
            $table->string('manufacturer_transaction_type')->nullable();
            $table->integer('manufacturer_transaction_id')->nullable();
        });
    }
};
