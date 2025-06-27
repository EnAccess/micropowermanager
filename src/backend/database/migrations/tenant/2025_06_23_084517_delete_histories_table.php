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
        Schema::connection('tenant')->dropIfExists('histories');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->create('histories', function (Blueprint $table) {
            $table->increments('id');
            $table->morphs('target');
            $table->text('content');
            $table->string('action', 6);
            $table->string('field', 20)->nullable();
            $table->timestamps();
        });
    }
};
