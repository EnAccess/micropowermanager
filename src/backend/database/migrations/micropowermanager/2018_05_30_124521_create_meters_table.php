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
        Schema::connection('tenant')->create('meters', function (Blueprint $table) {
            $table->increments('id');
            $table->string('serial_number')->unique();
            $table->integer('meter_type_id');
            $table->boolean('in_use')->default(0);
            $table->integer('manufacturer_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('meters');
    }
};
