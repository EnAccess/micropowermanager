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
        Schema::connection('tenant')->dropIfExists('subscriptions');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->create('subscriptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('upgrade_id');
            $table->date('expires');
            $table->string('transaction_id');
            $table->timestamps();
        });
    }
};
