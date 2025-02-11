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
        Schema::connection('tenant')->create('people', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 50)->nullable();
            $table->string('education', 90)->nullable();
            $table->string('name', 60);
            $table->string('surname', 60);
            $table->date('birth_date')->nullable();
            $table->string('sex', 6)->nullable();
            $table->integer('nationality')->nullable();
            $table->integer('is_customer')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('people');
    }
};
