<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAngazaTransactionsTableFields extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('angaza_transactions', function (Blueprint $table) {
            $table->integer('status')->default(-1);
            $table->decimal('amount', 10, 2);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('angaza_transactions', function (Blueprint $table) {
            $table->dropColumn(['status', 'amount']);
        });
    }
}
