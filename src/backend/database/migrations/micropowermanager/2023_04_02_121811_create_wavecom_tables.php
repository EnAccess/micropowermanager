<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const TABLE_NAME = 'wavecom_transactions';

    public function up() {
        if (!Schema::hasTable(self::TABLE_NAME)) {
            Schema::create(self::TABLE_NAME, function (Blueprint $table) {
                $table->increments('id');
                $table->string('transaction_id')->unique();
                $table->string('sender');
                $table->string('message');
                $table->integer('amount')->unsigned();
                $table->integer('status')->unsigned();
                $table->timestamps();
            });
        }
    }

    public function down() {
        Schema::dropIfExists(self::TABLE_NAME);
    }
};
