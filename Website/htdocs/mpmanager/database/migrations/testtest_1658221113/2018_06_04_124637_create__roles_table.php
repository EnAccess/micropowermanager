<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role_definitions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('role_name');
        });


        Schema::create('roles', function (Blueprint $table) {
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
    public function down()
    {
        Schema::dropIfExists('person_roles');
    }
};
