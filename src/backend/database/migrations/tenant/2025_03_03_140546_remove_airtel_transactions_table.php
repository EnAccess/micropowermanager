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
        Schema::connection('tenant')->dropIfExists('airtel_transactions');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->create('airtel_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('interface_id');
            $table->string('business_number');
            $table->string('trans_id');
            $table->string('tr_id');
            $table->integer('status')->default(0);
            $table->timestamps();
        });

        Schema::connection('tenant')->table('airtel_transactions', function (Blueprint $table) {
            $table->string('manufacturer_transaction_type')->nullable();
            $table->integer('manufacturer_transaction_id')->nullable();
        });
    }
};
