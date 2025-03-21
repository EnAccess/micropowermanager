<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('meter_types');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::connection('tenant')->create('meter_types', function (Blueprint $table) {
            $table->increments('id');
            $table->boolean('online')->default(false);
            $table->integer('phase')->default(1);
            $table->integer('max_current')->default(10);
            $table->timestamps();
        });
    }
};
