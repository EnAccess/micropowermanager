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
        Schema::connection('tenant')->create('third_party_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id');
            $table->integer('status');
            $table->string('description')->nullable();
            $table->string('manufacturer_transaction_type')->nullable();
            $table->integer('manufacturer_transaction_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('third_party_transactions');
    }
};
