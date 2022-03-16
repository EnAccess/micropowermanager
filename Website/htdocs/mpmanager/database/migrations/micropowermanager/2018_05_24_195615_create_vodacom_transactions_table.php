<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vodacom_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('conversation_id')->unique();
            $table->string('originator_conversation_id')->unique();
            $table->string('mpesa_receipt');
            $table->dateTime('transaction_date');
            $table->string('transaction_id')->unique();
            $table->integer('status')->default(0);
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
        Schema::dropIfExists('vodacom_transactions');
    }
};
