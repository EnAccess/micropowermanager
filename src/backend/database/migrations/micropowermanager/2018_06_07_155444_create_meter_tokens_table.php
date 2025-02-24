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
        Schema::connection('tenant')->create('meter_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('transaction_id')->unique();
            $table->integer('meter_id');
            $table->string('token');
            $table->double('energy'); // the number of kwH's
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('meter_tokens');
    }
};
