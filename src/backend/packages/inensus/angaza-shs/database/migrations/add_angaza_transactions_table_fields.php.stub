<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAngazaTransactionsTableFields extends Migration
{
    public function up()
    {
        Schema::table('angaza_transactions', function (Blueprint $table) {
            $table->integer('status')->default(-1);
            $table->decimal('amount', 10, 2);
        });
    }

    public function down()
    {
        Schema::table('angaza_transactions', function (Blueprint $table) {
            $table->dropColumn(['status', 'amount']);
        });
    }
}
