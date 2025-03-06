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
        Schema::connection('tenant')->create('role_definitions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('role_name');
        });

        Schema::connection('tenant')->create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->morphs('role_owner');
            $table->integer('role_definition_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('role_definitions');
        Schema::connection('tenant')->dropIfExists('roles');
    }
};
