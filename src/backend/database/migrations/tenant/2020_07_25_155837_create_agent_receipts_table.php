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
        Schema::connection('tenant')->create('agent_receipts', function (Blueprint $table) {
            $table->id();
            $table->integer('agent_id');
            $table->integer('user_id');
            $table->double('amount');
            $table->integer('last_controlled_balance_history_id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('agent_receipts');
    }
};
