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
        Schema::connection('tenant')->dropIfExists('restrictions');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->create('restrictions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('target');
            $table->integer('default');
            $table->integer('limit');
            $table->timestamps();
        });
    }
};
