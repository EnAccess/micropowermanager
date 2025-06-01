<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSunKingTransactionsTableFields extends Migration
{
    public function up()
    {
        Schema::table('sun_king_transactions', function (Blueprint $table) {
            $table->integer('status')->default(-1)->after('id');
            $table->decimal('amount', 10, 2)->after('status');
        });
    }

    public function down()
    {
        Schema::table('sun_king_transactions', function (Blueprint $table) {
            $table->dropColumn(['status', 'amount']);
        });
    }
} 