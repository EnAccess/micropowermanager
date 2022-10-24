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
        Schema::connection('shard')->create('transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('original_transaction_id');
            $table->string('original_transaction_type');
            $table->integer('amount');
            $table->enum('type', ['energy', 'deferred_payment', 'unknown'])->default('unknown');
            $table->string('sender');
            $table->string('message');
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
        Schema::connection('shard')->dropIfExists('transactions');
    }
};
