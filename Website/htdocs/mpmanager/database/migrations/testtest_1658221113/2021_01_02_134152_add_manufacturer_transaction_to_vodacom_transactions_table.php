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
        Schema::connection('shard')->table('vodacom_transactions', function (Blueprint $table) {
            $table->string('manufacturer_transaction_type')->nullable();
            $table->integer('manufacturer_transaction_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('shard')->table('vodacom_transactions', function (Blueprint $table) {
            //
        });
    }
};
